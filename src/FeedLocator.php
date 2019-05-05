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
use FeedLocator\Mixin as Tr;
use GuzzleHttp\Promise as func;
use Psr\Log\NullLogger;

/**
 * `FeedLocator\FeedLocator` is the primary entry point for Feed Locator.
 *
 * @see https://github.com/simplepie/simplepie/blob/master/library/SimplePie/Locator.php
 * @see https://web.archive.org/web/20100620085023/http://diveintomark.org/archives/2002/08/15/ultraliberal_rss_locator
 *
 * 0. At every step, RSS feeds are minimally verified to make sure they are really RSS feeds.
 * 1. If the URI points to an RSS feed, it is simply returned; otherwise the page is downloaded and the real fun begins.
 * 2. Feeds pointed to by LINK tags in the header of the page (RSS autodiscovery)
 * 3. <A> links to feeds on the same server ending in ".rss", ".rdf", or ".xml"
 * 4. <A> links to feeds on the same server containing "rss", "rdf", or "xml"
 * 5. <A> links to feeds on external servers ending in ".rss", ".rdf", or ".xml"
 * 6. <A> links to feeds on external servers containing "rss", "rdf", or "xml"
 */
class FeedLocator
{
    use Tr\GuzzleClientTrait;
    use Tr\LoggerTrait;

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
        $this->queue       = func\iter_for($uri);
        $this->results     = new ArrayIterator();
    }

    public function run()
    {
        $handler = function (string $uri, ArrayIterator $queue) {
            return $this->client->getAsync($uri)
                ->then(ValidFeed::isFeed($uri, $queue, $this->logger, $this->results))
                ->then(null, static function ($reason): void {
                    die($reason . \PHP_EOL);
                });
        };

        $mapper    = new Http\MapIterator($this->queue, $handler);
        $generator = new Http\ExpectingIterator($mapper);

        return func\each_limit($generator, $this->concurrency);
    }

    public function getResults(): ArrayIterator
    {
        return $this->results;
    }
}
