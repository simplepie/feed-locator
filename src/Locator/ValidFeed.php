<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Locator;

use ArrayIterator;
use FeedLocator\Enum as E;
use FeedLocator\Queue;
use FeedLocator\Validator\AtomValidator;
use FeedLocator\Validator\JsonFeedValidator;
use FeedLocator\Validator\RdfValidator;
use FeedLocator\Validator\RssValidator;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ValidFeed
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * Parses the content of the PSR-7 body and validates its content for known feed-like markers.
     *
     * @param string          $uri      The URI that we are parsing, and that the PSR-7 body contains the contents of.
     * @param Queue           $queue    The Queue object which keeps track of the work that needs to be done.
     * @param LoggerInterface $logger   An instantiated PSR-3 logger object.
     * @param ArrayIterator   &$results The collection of matched results.
     *
     * @return callable A _thennable_ which returns a fulfilled or rejected promise.
     *
     * phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
     */
    public static function parse(
        string $uri,
        Queue $queue,
        LoggerInterface $logger,
        ArrayIterator &$results
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
        return static function (ResponseInterface $response) use ($uri, $queue, $logger, $results): PromiseInterface {
            // phpcs:enable

            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            // Memoize these checks
            $isFeed    = false;
            $typeCheck = [
                'isAtom'     => AtomValidator::isFeed($response),
                'isJsonFeed' => JsonFeedValidator::isFeed($response),
                'isRss'      => RssValidator::isFeed($response),
                'isRdf'      => RdfValidator::isFeed($response),
            ];

            $contentType = $response->getHeader('content-type');
            $contentType = \end($contentType);

            $effectiveUri = $response->getHeader('x-effective-uri');
            $effectiveUri = \end($effectiveUri);

            $logger->info($effectiveUri, $typeCheck);

            if ($typeCheck['isRss']) {
                $isFeed = true;
                $results->append([$effectiveUri, E\FeedFormat::RSS, $contentType]);
            } elseif ($typeCheck['isAtom']) {
                $isFeed = true;
                $results->append([$effectiveUri, E\FeedFormat::ATOM, $contentType]);
            } elseif ($typeCheck['isRdf']) {
                $isFeed = true;
                $results->append([$effectiveUri, E\FeedFormat::RDF, $contentType]);
            } elseif ($typeCheck['isJsonFeed']) {
                $isFeed = true;
                $results->append([$effectiveUri, E\FeedFormat::JSONFEED, $contentType]);
            }

            // If this a feed, we're done. Bail out.
            if ($isFeed) {
                return new RejectedPromise($effectiveUri);
            }

            // Else, proceed with parsing the HTML for goodies.
            return new FulfilledPromise($response);
        };
    }
}
