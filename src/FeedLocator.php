<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator;

use ArrayIterator;
use FeedLocator\Locator\Autodiscovery;
use FeedLocator\Locator\KnownGood;
use FeedLocator\Locator\Scrape;
use FeedLocator\Locator\Status;
use FeedLocator\Locator\ValidFeed;
use FeedLocator\Mixin as Tr;
use GuzzleHttp\Promise as func;
use Psr\Log\NullLogger;
use SimplePie\UtilityPack\Mixin as UpTr;

/**
 * `FeedLocator\FeedLocator` is the primary entry point for Feed Locator.
 *
 * @see https://web.archive.org/web/20100620085023/http://diveintomark.org/archives/2002/08/15/ultraliberal_rss_locator
 */
class FeedLocator
{
    use Tr\GuzzleClientTrait;
    use UpTr\LoggerTrait;

    public $count = 0;

    /**
     * The results object which holds the results of the work tasks which have already been performed.
     *
     * @var ArrayIterator
     */
    protected $results;

    /**
     * The number of parallel threads to use.
     *
     * @var int
     */
    protected $concurrency;

    /**
     * The queue object which holds references to the work tasks which still need to be performed.
     *
     * @var ArrayIterator
     */
    protected $queue;

    /**
     * The original URI that was passed into the locator.
     *
     * @var string
     */
    protected $sourceUri;

    /**
     * Stores whether or not the source URI is being fetch and parsed for the first time.
     *
     * @var bool
     */
    protected $firstSource;

    /**
     * Constructs a new instance of this class.
     *
     * @param string   $uri         The URI on which to begin the auto-discovery scanning.
     * @param int|null $concurrency The number of parallel threads to use. Should be equal to, or fewer than, the
     *                              number of CPU cores running the code.
     */
    public function __construct(string $uri, int $concurrency = 1)
    {
        $this->logger      = new NullLogger();
        $this->concurrency = $concurrency;
        $this->results     = new ArrayIterator();
        $this->sourceUri   = $uri;
        $this->firstSource = true;
        $this->queue       = $this->queueFor($this->sourceUri);
    }

    public function run()
    {
        $handler = function (string $uri, Queue $queue) {
            return $this->client->getAsync($uri)
                ->then(
                    ValidFeed::parse($uri, $queue, $this->logger, $this->sourceUri, $this->firstSource, $this->results)
                )
                ->then(KnownGood::parse($uri, $queue, $this->logger))
                ->then(Autodiscovery::parse($uri, $queue, $this->logger))
                ->then(Scrape::parse($uri, $queue, $this->logger, $this->sourceUri, Scrape::MODE_SAME_DOMAIN))
                ->then(Scrape::parse($uri, $queue, $this->logger, $this->sourceUri, Scrape::MODE_ROOT_DOMAIN))
                ->then(Status::counter($queue, $this->logger, $this->results))
                ->otherwise(static::handleReject());
        };

        $mapper    = new Http\MapIterator($this->queue, $handler);
        $generator = new Http\ExpectingIterator($mapper);

        return func\each_limit($generator, $this->concurrency);
    }

    /**
     * Return the list of feeds that have been validated as such.
     */
    public function getResults(): ArrayIterator
    {
        return $this->results;
    }

    /**
     * We use rejections to manage promise flow. This a default `void` handler.
     */
    protected static function handleReject(): callable
    {
        return static function (string $reason): void {
        };
    }

    /**
     * Accepts an iterable object and wraps it into a Queue object.
     *
     * @param iterable $value The iterable object to wrap into a Queue object.
     */
    protected function queueFor($value): Queue
    {
        if ($value instanceof Queue) {
            return $value;
        }

        if (\is_array($value)) {
            return new Queue($value);
        }

        return new Queue([$value]);
    }
}
