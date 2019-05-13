<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GMW;
use GuzzleHttp\TransferStats;
use Psr\Log\LoggerInterface;
use SimplePie\UtilityPack\Util\Bytes;

class DefaultConfig
{
    /**
     * Default HandlerStack with pre-included middleware.
     */
    public static function handlerStack(LoggerInterface $logger): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::saveEffectiveUri());
        $stack->push(
            GMW::retry(
                Middleware::createRetryHandler($logger),
                [Middleware::class, 'exponentialDelay']
            )
        );

        return $stack;
    }

    /**
     * A default handler for Guzzle's `on_stats` event, which logs data about the request.
     *
     * @param LoggerInterface $logger A PSR-3 logger.
     */
    public static function statsHandler(LoggerInterface $logger): callable
    {
        return static function (TransferStats $stats) use ($logger): void {
            $logger->info($stats->getEffectiveUri(), [
                'time'       => $stats->getTransferTime(),
                'mem_script' => Bytes::format(\memory_get_usage()),
                'mem_zend'   => Bytes::format(\memory_get_usage(true)),
            ]);
        };
    }

    /**
     * Standard Guzzle client options that can be updated and passed into a new client.
     *
     * @param LoggerInterface $logger A PSR-3 logger.
     */
    public static function clientOptions(LoggerInterface $logger): array
    {
        return [
            'cookies'         => true,
            'connect_timeout' => 3.0,
            'timeout'         => 3.0,
            'http_errors'     => false,
            'on_stats'        => static::statsHandler($logger),
            'handler'         => static::handlerStack($logger),
        ];
    }
}
