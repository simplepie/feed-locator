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
use FeedLocator\Queue;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Status
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    public static function counter(Queue $queue, LoggerInterface $logger, ArrayIterator &$results)
    {
        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        return static function (ResponseInterface $response) use ($queue, $logger, $results): PromiseInterface {
            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            $logger->notice('Workload size', [
                'queue'   => \count(\array_values($queue->getArrayCopy())),
                'results' => \count($results),
            ]);

            return new FulfilledPromise($response);
        };
    }
}
