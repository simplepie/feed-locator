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

interface ValidatorInterface
{
    /**
     * Determines whether or not a PSR-7 `ResponseInterface` contains a feed.
     *
     * @param ResponseInterface $response        A PSR-7 `ResponseInterface` class.
     * @param bool              $contentSniffing Whether or not to use a method called _Content Sniffing_ to make this
     *                                           determination. A value of `true` means that a small portion of the
     *                                           response body will be read and scanned for particular feed markers. A
     *                                           value of `false` means that we will rely exclusively on a known set of
     *                                           HTTP `Content-Type` response headers and will reject anything that does
     *                                           not match. The default value is `true`.
     */
    public static function isFeed(ResponseInterface $response, bool $contentSniffing = true): bool;
}
