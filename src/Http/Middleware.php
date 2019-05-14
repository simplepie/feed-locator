<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Middleware
{
    /**
     * Constructs a new instance of this class.
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
        return static function (callable $handler) {
            return static function (RequestInterface $request, array $options) use ($handler) {
                return $handler($request, $options)->then(static function (ResponseInterface $response) use ($request) {
                    return $response->withHeader('X-Effective-URI', $request->getUri());
                });
            };
        };
    }
}
