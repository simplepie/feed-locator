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
    // Search for matching URIs on the same root-level domain name.
    public const MODE_ROOT_DOMAIN = 0x00000100;

    // Search for matching URIs on the exact same domain name.
    public const MODE_SAME_DOMAIN = 0x00000010;

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
     * @param string          $uri        The URI that we are parsing, and that the PSR-7 body contains the contents of.
     * @param Queue           $queue      The Queue object which keeps track of the work that needs to be done.
     * @param LoggerInterface $logger     An instantiated PSR-3 logger object.
     * @param string          &$sourceUri The original source URI that was originally passed into the `FeedLocator`.
     * @param int             $mode       The mode with which to parse the content. The default value is to search for
     *                                    matching URIs on the exact same domain name.
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

        /*
         * A _thennable_ which returns a fulfilled or rejected promise.
         *
         * @param ResponseInterface $response A PSR-7 response object.
         *
         * phpcs:disable Generic.Files.LineLength.MaxExceeded
         */
        return static function (ResponseInterface $response) use ($uri, $queue, $logger, &$sourceUri, $mode): PromiseInterface {
            // phpcs:enable

            $logger->debug(\sprintf(
                'The closure from `%s` is running in %s mode.',
                __CLASS__,
                static::getModePhrase($mode)
            ));

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
                    $logger->debug(\sprintf('✓ "%s" is part of the "%s" domain', $linkHost, $sourceRootHost), [
                        'uri' => (string) $link,
                    ]);
                } else {
                    $logger->debug(\sprintf('‼︎ "%s" is NOT part of the "%s" domain', $linkHost, $sourceRootHost), [
                        'uri' => (string) $link,
                    ]);
                }
            }

            $response->getBody()->rewind();

            return new FulfilledPromise($response);
        };
    }

    /**
     * Keywords which can be used to identify feeds in URIs.
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
     * @param array       $keywords   The list of keywords to use for identifying feeds in URIs.
     * @param string      &$sourceUri The original source URI that was originally passed into the `FeedLocator`.
     * @param string|null $host       The current host of the URI being parsed.
     * @param int         $mode       The mode with which to parse the content. The default value is to search for
     *                                matching URIs on the exact same domain name.
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
     * Parse the hostname to identify the root-level domain name.
     *
     * @param string $host The current host of the URI being parsed.
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
     * A variable portion of the XPath 1.0 query used when identifying the exact same domain name.
     *
     * @param Uri $host The current host of the URI being parsed.
     */
    public static function xpathSameDomain(Uri $host): string
    {
        return \sprintf(
            'starts-with(@href, "%s") or starts-with(@href, "/")',
            \sprintf('%s://%s', $host->getScheme(), $host->getHost())
        );
    }

    /**
     * A variable portion of the XPath 1.0 query used when identifying the same root-level domain name.
     *
     * @param Uri $host The current host of the URI being parsed.
     */
    public static function xpathRootDomain(Uri $host): string
    {
        return \sprintf(
            'contains(@href, "%s")',
            static::parseHost($host->getHost())
        );
    }

    /**
     * A phrase which represents the mode of parsing. Useful for log messages.
     *
     * @param int $mode One of the `MODE_*` constants.
     */
    public static function getModePhrase(int $mode): ?string
    {
        switch ($mode) {
            case static::MODE_ROOT_DOMAIN:
                return 'same root domain';

            case static::MODE_SAME_DOMAIN:
                return 'same full domain';

            default:
                return null;
        }
    }
}
