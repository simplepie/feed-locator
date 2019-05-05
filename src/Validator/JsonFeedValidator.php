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

/**
 * Validates whether or not a response is a valid type of feed.
 */
class JsonFeedValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * Determines whether or not a PSR-7 `ResponseInterface` contains a JSONFeed feed.
     *
     * @param ResponseInterface $response A PSR-7 `ResponseInterface` class.
     */
    public static function isFeed(ResponseInterface $response): bool
    {
        /** @var \Psr\Http\Message\StreamInterface */
        $body = $response->getBody();

        /** @var string */
        $firstBits = $body->read(100);
        $body->rewind();

        return (bool) \preg_match('%https://jsonfeed.org/version/1%im', $firstBits);
    }
}
