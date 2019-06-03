<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Middleware
{
    /**
     * Constructs a new instance of this class.
     *
     * @psalm-suppress UnusedMethod
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * Determine the final URI after any redirects, and store it in the response headers.
     */
    public static function saveEffectiveUri(): callable
    {
        return static function (callable $handler): callable {
            /**
             * @var callable
             *
             * @psalm-suppress MixedInferredReturnType
             */
            return static function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
                /**
                 * @var PromiseInterface
                 *
                 * @psalm-suppress MixedReturnStatement
                 * @psalm-suppress MixedMethodCall
                 *
                 * phpcs:disable Generic.Files.LineLength.MaxExceeded
                 */
                return $handler($request, $options)->then(static function (ResponseInterface $response) use ($request): ResponseInterface {
                    // phpcs:enable

                    /**
                     * @var ResponseInterface
                     */
                    return $response->withHeader('X-Effective-URI', (string) $request->getUri());
                });
            };
        };
    }
}
