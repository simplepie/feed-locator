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
 * Leverages the "autodiscovery" method for discovering a feed from an HTML page.
 *
 * @see https://www.iana.org/assignments/media-types/media-types.xhtml
 */
class Autodiscovery
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * Parses the content of the PSR-7 body and looks for matches that appear to be Autodiscovery markers.
     *
     * @param string          $uri    The URI that we are parsing, and that the PSR-7 body contains the contents of.
     * @param Queue           $queue  The Queue object which keeps track of the work that needs to be done.
     * @param LoggerInterface $logger An instantiated PSR-3 logger object.
     *
     * @return callable A _thennable_ which returns a fulfilled or rejected promise.
     */
    public static function parse(string $uri, Queue $queue, LoggerInterface $logger): callable
    {
        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        /*
         * A _thennable_ which returns a fulfilled or rejected promise.
         *
         * @param ResponseInterface $response A PSR-7 response object.
         */
        return static function (ResponseInterface $response) use ($uri, $queue, $logger): PromiseInterface {
            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            $effectiveUri = $response->getHeader('x-effective-uri');
            $effectiveUri = \end($effectiveUri);
            $effectiveUri = new Uri($effectiveUri);

            $parser   = new HtmlParser($response->getBody(), $logger);
            $includes = static::formatIncludeAsXpath(static::formatsAll());
            $excludes = static::formatExcludeAsXpath();

            $query   = static::formatQuery($includes, $excludes);
            $results = $parser->xpath()->query($query);

            // How many results did the XPath query result in?
            $logger->debug($query, [
                'matches' => \count($results),
            ]);

            foreach ($results as $result) {
                $link = new Uri((string) $result->nodeValue);
                $link = UriResolver::resolve($effectiveUri, $link);

                $queue->append((string) $link);
            }

            $response->getBody()->rewind();

            return new FulfilledPromise($response);
        };
    }

    /**
     * A list of known-good media types for the Atom feed format.
     *
     * @see https://tools.ietf.org/html/rfc4287
     * @see https://www.intertwingly.net/wiki/pie/AutoDiscovery
     */
    public static function formatsAtom(): array
    {
        return [
            'application/atom+xml', // Canonical
        ];
    }

    /**
     * A list of known-good media types for the JSONFeed feed format.
     *
     * @see https://jsonfeed.org/version/1
     * @see https://github.com/brentsimmons/JSONFeed/pull/32
     */
    public static function formatsJson(): array
    {
        return [
            'application/json',      // Original canonical
            'application/feed+json',
        ];
    }

    /**
     * A list of known-good media types for RDF documents, which _may_ be feeds.
     *
     * @see http://purl.org/rss/1.0/spec
     */
    public static function formatsRdf(): array
    {
        return [
            'application/rdf',
            'application/rdf+xml',
            'application/x-rdf',
            'application/x-rdf+xml',
            'text/rdf',
            'text/rdf+xml',
            'text/x-rdf',
            'text/x-rdf+xml',
        ];
    }

    /**
     * A list of known-good media types for the RSS feed format.
     *
     * @see http://www.rssboard.org/rss-autodiscovery
     * @see https://www.intertwingly.net/wiki/pie/RssAutoDiscovery
     */
    public static function formatsRss(): array
    {
        return [
            'application/rss',
            'application/rss+xml', // Canonical.
            'application/x-rss',
            'application/x-rss+xml',
            'text/rss',
            'text/rss+xml',
            'text/x-rss',
            'text/x-rss+xml',
        ];
    }

    /**
     * A list of generic XML media types + the other XML feed media types.
     */
    public static function formatsXml(): array
    {
        return \array_merge(static::formatsAtom(), static::formatsRdf(), static::formatsRss(), [
            'application/xml',
            'application/x-xml',
            'text/xml',
            'text/x-xml',
        ]);
    }

    /**
     * A list of all supported media types for feeds.
     */
    public static function formatsAll(): array
    {
        return \array_merge(static::formatsJson(), static::formatsXml());
    }

    /**
     * Generates the proper XPath 1.0 format for excluding certain keywords.
     */
    public static function formatExcludeAsXpath(): string
    {
        $exclude = [];
        $not     = [
            'dtd',
            'external-parsed-entity',
            'oembed',
            'xhtml',
            'xml-patch',
        ];

        $exclude = \array_merge($exclude, \array_map(static function ($e) {
            return \sprintf('not(contains(@type, "%s"))', $e);
        }, $not));

        return \implode(' and ', $exclude);
    }

    /**
     * Generates the proper XPath 1.0 format for excluding certain keywords.
     *
     * @param iterable ...$formats A variadic argument for the formats for which you want to generate valid XPath 1.0.
     */
    public static function formatIncludeAsXpath(...$formats): string
    {
        $include = [];

        foreach ($formats as $format) {
            $include = \array_merge($include, \array_map(static function ($e) {
                return \sprintf('contains(@type, "%s")', $e);
            }, $format));
        }

        return \implode(' or ', $include);
    }

    /**
     * Generates the final XPath 1.0 query, including the list of _includes_ and _excludes_.
     *
     * @param string $include The result from `formatIncludeAsXpath()`.
     * @param string $exclude The result from `formatExcludeAsXpath()`.
     */
    public static function formatQuery(string $include, string $exclude): string
    {
        return \sprintf(
            'descendant-or-self::link[@rel="alternate"][(%s) and (%s)]/@href',
            $include,
            $exclude
        );
    }
}
