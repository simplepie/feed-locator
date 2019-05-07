<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Locator;

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
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
    }

    /**
     * [parse description].
     *
     * @param string          $uri    [description]
     * @param Queue           $queue  [description]
     * @param LoggerInterface $logger [description]
     */
    public static function parse(string $uri, Queue $queue, LoggerInterface $logger): callable
    {
        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        return static function (ResponseInterface $response) use ($uri, $queue, $logger): PromiseInterface {
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

            $query   = static::formatQuery(static::feedKeywords(), $effectiveHost);
            $results = $parser->xpath()->query($query);

            $logger->debug($query, [
                'matches' => \count($results),
            ]);

            foreach ($results as $result) {
                $link = new Uri((string) $result->nodeValue);
                $link = (string) UriResolver::resolve($effectiveUri, $link);

                $queue->append($link);
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
     * @param array       $keywords [description]
     * @param string|null $host     [description]
     */
    public static function formatQuery(array $keywords, ?string $host = null): string
    {
        $include     = [];
        $hostSegment = '';

        if (null !== $host) {
            $hostSegment = \sprintf('starts-with(@href, "%s") or', $host);
        }

        $include = \array_merge($include, \array_map(static function ($e) {
            return \sprintf('contains(@href, "%s")', $e);
        }, $keywords));

        $include = \implode(' or ', $include);

        return \sprintf(
            'descendant-or-self::a[(%s starts-with(@href, "/")) and (%s)]/@href',
            $hostSegment,
            $include
        );
    }
}
