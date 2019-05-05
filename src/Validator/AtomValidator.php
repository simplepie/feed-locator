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
class AtomValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public static function isFeed(ResponseInterface $response, bool $contentSniffing = true): bool
    {
        if ($contentSniffing) {
            return static::scanBodyFor($response, '%<feed\s?([^>]*)http://www.w3.org/2005/Atom([^>]*)>%im', 500);
        }

        // `application/atom+xml`, `application/xml`, `text/xml`
        return static::scanContentTypeFor($response, '%(application|text)/(atom\+)?xml%i');
    }
}
