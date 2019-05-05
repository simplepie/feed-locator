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
     * {@inheritdoc}
     */
    public static function isFeed(ResponseInterface $response, bool $contentSniffing = true): bool
    {
        if ($contentSniffing) {
            return static::scanBodyFor($response, '%https://jsonfeed.org/version/1%im', 100);
        }

        // `application/feed+json`, `application/json`
        return static::scanContentTypeFor($response, '%application/(feed\+)?json%i');
    }
}
