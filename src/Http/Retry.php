<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Log\LoggerInterface;

class Retry
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * Simplified function for creating a new Guzzle request handler with default retry rules.
     *
     * @param LoggerInterface $logger An instantiated PSR-3 logger object.
     */
    public static function defaultHandler(LoggerInterface $logger): callable
    {
        return GuzzleMiddleware::retry(
            static::createRetryHandler($logger),
            [static::class, 'exponentialDelay']
        );
    }

    /**
     * Creates a new retry handler callable.
     *
     * @param LoggerInterface $logger     An instantiated PSR-3 logger object.
     * @param int             $maxRetries The maximum number of retries to perform before quitting.
     */
    public static function createRetryHandler(LoggerInterface $logger, int $maxRetries = 5): callable
    {
        /*
         * A handler callable which determines the rules of performing a request retry.
         *
         * @param mixed $retries              The retries that have already been performed.
         * @param Psr7Request $request        A PSR-7 request object.
         * @param Psr7Response $response      A PSR-7 response object.
         * @param RequestException $exception A PSR-7 exception object.
         *
         * phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
         */
        return static function (
            $retries,
            Psr7Request $request,
            Psr7Response $response = null,
            RequestException $exception = null
        ) use ($logger, $maxRetries) {
            // phpcs:enable

            if ($retries >= $maxRetries) {
                return false;
            }

            if (!(static::isServerError($response) || static::isConnectError($exception))) {
                return false;
            }

            $logger->warning(\sprintf(
                'Retrying %s %s (%s/%s), %s',
                $request->getMethod(),
                $request->getUri(),
                $retries + 1,
                $maxRetries,
                $response
                    ? 'status code: ' . $response->getStatusCode()
                    : $exception->getMessage()
            ), [
                $request->getHeader('Host')[0],
            ]);

            return true;
        };
    }

    /**
     * Delay function that calculates an exponential delay. Exponential backoff with jitter, 100ms base, 20 sec ceiling.
     *
     * @param int $retries The number of retries that have already been attempted.
     */
    public static function exponentialDelay(int $retries): int
    {
        return \random_int(0, (int) \min(20000, (int) 2 ** $retries * 100));
    }

    /**
     * Determines whether or not this is a server-related error.
     *
     * @param Psr7Response|null $response A PSR-7 response object.
     */
    protected static function isServerError(Psr7Response $response = null): bool
    {
        return $response && $response->getStatusCode() >= 500;
    }

    /**
     * Determines whether or not this is a client-related error.
     *
     * @param RequestException|null $exception A PSR-7 exception object.
     */
    protected static function isConnectError(RequestException $exception = null): bool
    {
        return $exception instanceof ConnectException;
    }
}
