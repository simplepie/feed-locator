<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Locator;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class EffectiveUri
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do not instantiate.
    }

    /**
     * If this is the first time this has run, update the source URI with the
     * effective source URI. This is most useful for cases where there is either
     * a 3xx-class redirect, or when the URI was entered without a scheme.
     *
     * @param string          &$sourceUri   The original source URI that was originally passed into the `FeedLocator`.
     * @param bool            &$firstSource A boolean which tracks whether or not this was the first time this has
     *                                      been run.
     * @param LoggerInterface $logger       An instantiated PSR-3 logger object.
     */
    public static function parse(string &$sourceUri, bool &$firstSource, LoggerInterface $logger): callable
    {
        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        return static function (ResponseInterface $response) use (&$sourceUri, &$firstSource, $logger): PromiseInterface {
            // phpcs:enable

            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            // Only update the source URI with the effective URI on the first pass
            if ($firstSource) {
                $effectiveUri = $response->getHeader('x-effective-uri');
                $effectiveUri = \end($effectiveUri);

                if ($sourceUri !== $effectiveUri) {
                    $logger->notice(\sprintf('Source URI has been updated from "%s" → "%s".', $sourceUri, $effectiveUri));
                    $sourceUri = $effectiveUri;
                }

                $firstSource = false;
            }

            return new FulfilledPromise($response);
        };
    }
}
