<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Mixin;

use GuzzleHttp\Client;
use SimplePie\UtilityPack\Util\Types;

/**
 * Shared code for working with Guzzle clients.
 */
trait GuzzleClientTrait
{
    /**
     * An instantiated Guzzle client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Sets a new instantiated Guzzle client.
     *
     * @param Client $client An instantiated Guzzle client.
     *
     * @return self
     */
    public function setGuzzleClient(Client $client)
    {
        $this->client = $client;

        // What are we logging with?
        $this->logger->debug(\sprintf(
            'Class `%s` configured to use `%s`.',
            Types::getClassOrType($this),
            Types::getClassOrType($this->client)
        ));

        return $this;
    }

    /**
     * Gets a new instantiated Guzzle client.
     *
     * @return Client An instantiated Guzzle client.
     */
    public function getGuzzleClient(): Client
    {
        return $this->client;
    }
}
