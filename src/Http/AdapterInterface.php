<?php
/**
 * Copyright (c) 2019 Ryan Parman <http://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use GuzzleHttp\Promise\Promise;

interface AdapterInterface
{
    /**
     * Performs the work to make the request.
     *
     * @param iterable $requests   An iterable list of PSR-7 Request objects that need to be sent.
     * @param iterable &$responses An iterable list where you can append your PSR-7 Response objects.
     *
     * @return Promise A Guzzle Promise object that can be `then`'d or `wait`'d.
     */
    public function __invoke(iterable $requests, iterable &$responses): Promise;
}
