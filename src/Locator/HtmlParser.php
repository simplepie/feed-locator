<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Locator;

class HtmlParser
{
    /**
     * @param ResponseInterface $response A PSR-7 `ResponseInterface` class.
     */
    public static function parseAutodiscovery(ResponseInterface $response): bool
    {
        /** @var \Psr\Http\Message\StreamInterface */
        $body = $response->getBody();

        /** @var string */
        $firstBits = $body->read(500);
        $body->rewind();
    }
}
