<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Validator;

use Psr\Http\Message\ResponseInterface;

abstract class AbstractValidator
{
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * Scans the body of a PSR-7 response for a matching pattern.
     *
     * @param ResponseInterface $response  A PSR-7 `ResponseInterface` class.
     * @param string            $regex     A PCRE-compatible regular expression pattern meant to
     *                                     positively-identify a feed.
     * @param int               $readBytes The number of bytes to read from the beginning of the
     *                                     document in order to apply the regex.
     */
    public static function scanBodyFor(ResponseInterface $response, string $regex, int $readBytes): bool
    {
        /** @var \Psr\Http\Message\StreamInterface */
        $body = $response->getBody();

        /** @var string */
        $firstBits = $body->read($readBytes);
        $body->rewind();

        // echo '--------------------------------------------------------------------------' . \PHP_EOL;
        // echo $response->getStatusCode() . \PHP_EOL;
        // echo $firstBits . \PHP_EOL;
        // echo '--------------------------------------------------------------------------' . \PHP_EOL;

        return (bool) \preg_match($regex, $firstBits);
    }

    /**
     * Scans the content-type header of a PSR-7 response for a matching pattern.
     *
     * @param ResponseInterface $response A PSR-7 `ResponseInterface` class.
     * @param string            $regex    A PCRE-compatible regular expression pattern meant to
     *                                    positively-identify a feed.
     */
    public static function scanContentTypeFor(ResponseInterface $response, string $regex): bool
    {
        $contentType = $response->getHeader('content-type');
        $contentType = \end($contentType);

        return (bool) \preg_match($regex, $firstBits);
    }
}
