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
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Promise as func;
use Psr\Http\Message\ResponseInterface;
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
     * The queue object which holds references to the work tasks which still need to be performed.
     *
     * @var ArrayIterator
     */
    protected $queue;

    /**
     * Constructs a new instance of this class.
     *
     * @param string      $uri         The URI on which to begin the auto-discovery scanning.
     * @param int|null    $concurrency The number of parallel threads to use. Should be equal to, or fewer than, the
     *                                 number of CPU cores running the code.
     * @param Client|null $client      An instantiated Guzzle 6 client object.
     */
    public function __construct(string $uri, int $concurrency = 5, Client $client = null)
    {
        $this->logger      = new NullLogger();
        $this->concurrency = $concurrency;
        $this->queue       = func\iter_for($uri);

        if ($client) {
            $this->client = $client;
        } else {
            $this->client = new Client([
                RequestOptions::CONNECT_TIMEOUT => 1.0,
                RequestOptions::TIMEOUT         => 1.0,
                RequestOptions::HTTP_ERRORS     => false,
                RequestOptions::STREAM          => true,
            ]);
        }
    }

    public function run()
    {
        $generator = new Http\MapIterator(
            $this->queue,
            function (string $url, ArrayIterator $queue) {
                return $this->client->getAsync($url)
                    ->then(static function (ResponseInterface $response) use ($url, $queue): void {
                        $hash = \bin2hex(\random_bytes(10));
                        $queue->append(
                            \sprintf('http://httpbin.org/anything/%s', $hash)
                        );
                    });
            }
        );

        $generator = new Http\ExpectingIterator($generator);

        return func\each_limit($generator, $this->concurrency);
    }
}
