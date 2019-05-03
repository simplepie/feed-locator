<?php
/**
 * Copyright (c) 2019 Ryan Parman <http://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\Promise;

class Guzzle6Adapter implements AdapterInterface
{
    /**
     * An instantiated Guzzle 6 client object.
     *
     * @var Client
     */
    protected $client;

    /**
     * The number of parallel threads to use.
     *
     * @var int
     */
    protected $concurrency;

    /**
     * Constructs a new instance of this class.
     *
     * @param Client  $client      An instantiated Guzzle 6 client object.
     * @param int|int $concurrency The number of parallel threads to use. Should be equal to, or fewer than, the
     *                             number of CPU cores running the code.
     */
    public function __construct(Client $client, int $concurrency = 5)
    {
        $this->client      = $client;
        $this->concurrency = $concurrency;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(iterable $requests, iterable &$responses): Promise
    {
        $pool = new Pool($this->client, $requests, [
            'concurrency' => $this->concurrency,
            'fulfilled'   => static function ($response) use (&$responses): void {
                $responses[] = $response;
            },
            'rejected' => static function ($reason): void {
                echo $reason . \PHP_EOL;
            },
        ]);

        return $pool->promise();
    }
}
