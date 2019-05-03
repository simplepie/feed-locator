<?php
/**
 * Copyright (c) 2019 Ryan Parman <http://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator;

use FeedLocator\Mixin as Tr;
use GuzzleHttp\Psr7;
use Psr\Log\LoggerInterface;
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
    use Tr\LoggerTrait;

    public $requestHandler;

    /**
     * Constructs a new instance of this class.
     */
    public function __construct()
    {
        // Default logger
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setRequestHandler(?callable $fn = null): void
    {
        $this->requestHandler = $fn ?: static function () {
            return true;
        };
    }

    public function exec(): void
    {
        $responses = [];

        $promise = ($this->requestHandler)($this->test(), $responses);
        $promise->wait(false);

        \print_r($responses);
    }

    private function test(): iterable
    {
        for ($i = 0; $i < 20; $i++) {
            yield new Psr7\Request('GET', \sprintf('http://httpbin.org/anything/%d', $i));
        }
    }
}
