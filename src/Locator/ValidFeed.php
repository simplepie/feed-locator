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
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ValidFeed
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
    }

    public static function isFeed(string $uri, Queue $queue, LoggerInterface $logger, ArrayIterator &$results)
    {
        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        return static function (ResponseInterface $response) use ($uri, $queue, $logger, $results): PromiseInterface {
            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            // Memoize these checks
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

            $logger->debug($effectiveUri, $typeCheck);

            if ($typeCheck['isRss']) {
                $results->append([
                    $effectiveUri,
                    E\FeedFormat::RSS,
                    $contentType,
                ]);
            } elseif ($typeCheck['isAtom']) {
                $results->append([
                    $effectiveUri,
                    E\FeedFormat::ATOM,
                    $contentType,
                ]);
            } elseif ($typeCheck['isRdf']) {
                $results->append([
                    $effectiveUri,
                    E\FeedFormat::RDF,
                    $contentType,
                ]);
            } elseif ($typeCheck['isJsonFeed']) {
                $results->append([
                    $effectiveUri,
                    E\FeedFormat::JSONFEED,
                    $contentType,
                ]);
            }

            return new FulfilledPromise($response);
        };
    }
}
