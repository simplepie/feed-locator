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
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Middleware
{
    /**
     * Determine the final URI after any redirects, and store it in the response headers.
     */
    public static function saveEffectiveUri(): callable
    {
        return static function (callable $handler) {
            return static function (RequestInterface $request, array $options) use ($handler) {
                return $handler($request, $options)->then(static function (ResponseInterface $response) use ($request) {
                    return $response->withHeader('X-Effective-URI', $request->getUri());
                });
            };
        };
    }

    /**
     * [createRetryHandler description].
     *
     * @param LoggerInterface $logger     [description]
     * @param int             $maxRetries [description]
     */
    public static function createRetryHandler(LoggerInterface $logger, int $maxRetries = 5): callable
    {
        /*
         * [$response description]
         *
         * @param int $retries                [description]
         * @param Psr7Request $request        [description]
         * @param Psr7Response $response      [description]
         * @param RequestException $exception [description]
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
                'Retrying %s %s %s/%s, %s',
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
        return mt_rand(0, (int) min(20000, (int) pow(2, $retries) * 100));
    }

    /**
     * [isServerError description].
     *
     * @param Psr7Response|null $response [description]
     */
    protected static function isServerError(Psr7Response $response = null): bool
    {
        return $response && $response->getStatusCode() >= 500;
    }

    /**
     * [isConnectError description].
     *
     * @param RequestException|null $exception [description]
     */
    protected static function isConnectError(RequestException $exception = null): bool
    {
        return $exception instanceof ConnectException;
    }
}
