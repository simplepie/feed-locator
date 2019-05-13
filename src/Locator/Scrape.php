<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Locator;

use FeedLocator\Exception\FeedLocatorException;
use FeedLocator\Parser\Html as HtmlParser;
use FeedLocator\Queue;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Falls back to scraping the contents of the page for indicators of feeds. Bleh.
 */
class Scrape
{
    public const MODE_ROOT_DOMAIN = 0x00000000;

    public const MODE_SAME_DOMAIN = 0x00000001;

    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * [parse description].
     *
     * @param string          $uri       [description]
     * @param Queue           $queue     [description]
     * @param LoggerInterface $logger    [description]
     * @param string          $sourceUri [description]
     * @param int             $mode      [description]
     *
     * phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
     */
    public static function parse(
        string $uri,
        Queue $queue,
        LoggerInterface $logger,
        string &$sourceUri,
        int $mode = self::MODE_SAME_DOMAIN
    ): callable {
        // phpcs:enable

        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        return static function (ResponseInterface $response) use ($uri, $queue, $logger, $sourceUri, $mode): PromiseInterface {
            // phpcs:enable

            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            $parser = new HtmlParser($response->getBody(), $logger);

            $effectiveUri  = $response->getHeader('x-effective-uri');
            $effectiveUri  = \end($effectiveUri);
            $effectiveUri  = new Uri($effectiveUri);
            $effectiveHost = \sprintf(
                '%s://%s',
                $effectiveUri->getScheme(),
                $effectiveUri->getHost()
            );

            $query   = static::formatQuery(static::feedKeywords(), $sourceUri, $effectiveHost, $mode);
            $results = $parser->xpath()->query($query);

            $logger->debug($query, [
                'matches' => \count($results),
            ]);

            // Determine the root-level domain of the starting URI
            $sourceHost     = new Uri($sourceUri);
            $sourceRootHost = static::parseHost($sourceHost->getHost());

            foreach ($results as $result) {
                // Parse and normalize the link
                $link     = new Uri((string) $result->nodeValue);
                $link     = UriResolver::resolve($effectiveUri, $link);
                $linkHost = $link->getHost();

                // Does the hostname of the link share the same root-level domain as our starting URI?
                if (false !== \mb_strpos($linkHost, $sourceRootHost)) {
                    $queue->append((string) $link);
                    $logger->debug(\sprintf('✓ %s is part of the %s domain', $linkHost, $sourceRootHost), [
                        'uri' => (string) $link,
                    ]);
                } else {
                    $logger->debug(\sprintf('‼︎ %s is NOT part of the %s domain', $linkHost, $sourceRootHost), [
                        'uri' => (string) $link,
                    ]);
                }
            }

            $response->getBody()->rewind();

            return new FulfilledPromise($response);
        };
    }

    /**
     * [feedKeywords description].
     */
    public static function feedKeywords(): array
    {
        return [
            'atom',
            'feed',
            'rdf',
            'rss',
            'xml',
        ];
    }

    /**
     * Generates the final XPath 1.0 query, including the list of _includes_ and _excludes_.
     *
     * @param array       $keywords  [description]
     * @param string      $sourceUri [description]
     * @param string|null $host      [description]
     * @param int         $mode      [description]
     *
     * phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
     */
    public static function formatQuery(
        array $keywords,
        string $sourceUri,
        ?string $host = null,
        int $mode = self::MODE_SAME_DOMAIN
    ): string {
        // phpcs:enable

        $include     = [];
        $hostSegment = 'starts-with(@href, "/")';

        if (null !== $host) {
            $shost = new Uri($sourceUri);

            $hostSegment = (self::MODE_SAME_DOMAIN === $mode)
                ? static::xpathSameDomain($shost)
                : static::xpathRootDomain($shost);
        }

        $include = \array_merge($include, \array_map(static function ($e) {
            return \sprintf('contains(@href, "%s")', $e);
        }, $keywords));

        $include = \implode(' or ', $include);

        return \sprintf(
            'descendant-or-self::a[(%s) and (%s)]/@href',
            $hostSegment,
            $include
        );
    }

    /**
     * [parseHost description].
     *
     * @param string $host [description]
     */
    public static function parseHost(string $host): string
    {
        $pieces  = \explode('.', $host);
        $rpieces = [];
        $count   = \count($pieces);

        if ($count < 2) {
            throw new FeedLocatorException(\sprintf('Cannot parse "%s" as a hostname.', $host));
        }

        if (2 === $count) {
            return $host;
        }

        \array_unshift($rpieces, \array_pop($pieces)); // TLD
        \array_unshift($rpieces, \array_pop($pieces)); // Root domain

        return \implode('.', $rpieces);
    }

    /**
     * [xpathSameDomain description].
     *
     * @param Uri $host [description]
     */
    public static function xpathSameDomain(Uri $host): string
    {
        return \sprintf(
            'starts-with(@href, "%s") or starts-with(@href, "/")',
            \sprintf('%s://%s', $host->getScheme(), $host->getHost())
        );
    }

    /**
     * [xpathRootDomain description].
     *
     * @param Uri $host [description]
     */
    public static function xpathRootDomain(Uri $host): string
    {
        return \sprintf(
            'contains(@href, "%s")',
            static::parseHost($host->getHost())
        );
    }
}
