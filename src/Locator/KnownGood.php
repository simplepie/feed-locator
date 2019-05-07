<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Locator;

use FeedLocator\Queue;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * As much as I hate this, some sites (e.g., Medium) make it difficult to parse the content of the page because of
 * cookie-based redirects. This is a set of parsers that can help in tricky situations like this.
 */
class KnownGood
{
    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
    }

    /**
     * [parse description].
     *
     * @param string          $uri    [description]
     * @param Queue           $queue  [description]
     * @param LoggerInterface $logger [description]
     */
    public static function parse(string $uri, Queue $queue, LoggerInterface $logger): callable
    {
        $logger->debug(\sprintf('`%s::%s` has been instantiated.', __CLASS__, __FUNCTION__));

        return static function (ResponseInterface $response) use ($uri, $queue, $logger): PromiseInterface {
            $logger->debug(\sprintf('The closure from `%s` is running.', __CLASS__));

            static::detectMedium($response, $queue, $logger);

            return new FulfilledPromise($response);
        };
    }

    /**
     * [detectMedium description].
     *
     * @param ResponseInterface $response [description]
     * @param Queue             $queue    [description]
     * @param LoggerInterface   $logger   [description]
     */
    public static function detectMedium(ResponseInterface $response, Queue $queue, LoggerInterface $logger): void
    {
        $poweredBy = $response->getHeader('x-powered-by');
        $poweredBy = \end($poweredBy);

        if ('Medium' === $poweredBy) {
            $logger->info('Known host: medium.com');

            $effectiveUri = $response->getHeader('x-effective-uri');
            $effectiveUri = \end($effectiveUri);
            $effectiveUri = new Uri($effectiveUri);

            $effectiveHost = \sprintf(
                '%s://%s',
                $effectiveUri->getScheme(),
                $effectiveUri->getHost()
            );

            $queue->append(\sprintf('%s/feed', $effectiveHost));
        }
    }
}
