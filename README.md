<div align="center"><img src="https://raw.githubusercontent.com/simplepie/.github/master/logo.png" width="500"><br></div>

----

# Feed Locator

**Feed Locator** is a _modern_ PHP implementation of Mark Pilgrim's [rssfinder.py](https://web.archive.org/web/20100620085023/http://diveintomark.org/projects/misc/rssfinder.py.txt) which was born from his desire for an [ultra-liberal RSS locator](https://web.archive.org/web/20100620085023/http://diveintomark.org/archives/2002/08/15/ultraliberal_rss_locator). This was the model for the [`SimplePie_Locator`](https://github.com/simplepie/simplepie/blob/master/library/SimplePie/Locator.php) class in SimplePie “OG”.

## Badges

### Health

[![Open Issues](http://img.shields.io/github/issues/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/issues)
[![Pull Requests](https://img.shields.io/github/issues-pr/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/pulls)
[![Contributors](https://img.shields.io/github/contributors/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/graphs/contributors)
[![Repo Size](https://img.shields.io/github/repo-size/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/pulse/monthly)
[![GitHub Commit Activity](https://img.shields.io/github/commit-activity/y/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/commits/master)
[![GitHub Last Commit](https://img.shields.io/github/last-commit/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/commits)

### Quality

[![Travis branch](https://img.shields.io/travis/simplepie/feed-locator/master.svg?style=for-the-badge&label=Travis%20CI)](https://travis-ci.org/simplepie/feed-locator)
[![Coveralls](https://img.shields.io/coveralls/github/simplepie/feed-locator/master.svg?style=for-the-badge)](https://coveralls.io/github/simplepie/feed-locator)
[![Code Quality](http://img.shields.io/scrutinizer/g/simplepie/feed-locator.svg?style=for-the-badge&label=Scrutinizer)](https://scrutinizer-ci.com/g/simplepie/feed-locator)
[![Symfony Insight](https://img.shields.io/sensiolabs/i/@@INSIGHT@@.svg?style=for-the-badge&label=Symfony%20Insight)](https://insight.symfony.com/projects/@@INSIGHT@@)

### Social

[![Author](http://img.shields.io/badge/author-@skyzyx-blue.svg?style=for-the-badge)](https://twitter.com/skyzyx)
[![Follow](https://img.shields.io/twitter/follow/simplepie_ng.svg?style=for-the-badge&label=Follow%20@simplepie_ng)](https://twitter.com/intent/follow?screen_name=simplepie_ng)
[![Blog](https://img.shields.io/badge/medium-simplepie--ng-blue.svg?style=for-the-badge)](https://medium.com/simplepie-ng)
[![Stars](https://img.shields.io/github/stars/simplepie/feed-locator.svg?style=for-the-badge&label=GitHub%20Stars)](https://github.com/simplepie/feed-locator/stargazers)

### Compliance

[![License](https://img.shields.io/github/license/simplepie/feed-locator.svg?style=for-the-badge)](https://github.com/simplepie/feed-locator/blob/master/LICENSE.md)

## Features

### Process

1. [X] At every step, feeds are minimally verified to make sure they are really feeds.
1. [X] If the URI points to a feed, it is simply returned; otherwise the page is downloaded and the real fun begins.
1. [X] Feeds pointed to by `<link>` tags in the header of the page. (This is standard autodiscovery.)
1. [X] `<a>` links to feeds on the same hostname, where the URIs contain `atom`, `feed`, `rdf`, `rss`, or `xml`.
1. [X] `<a>` links to feeds on a subdomain, where the URIs contain `atom`, `feed`, `rdf`, `rss`, or `xml`.
1. [ ] `<a>` links to feeds on a different domain, where the URIs contain `atom`, `feed`, `rdf`, `rss`, or `xml`.

### Discover Feeds!

* [X] Returns all the feeds it can possibly find!
* [ ] Can be configured to only perform standard autodiscovery.
* [ ] Can be configured to stop after discovering the first feed.
* [ ] Can be configured favor one language for a feed over another (e.g., XML vs. JSON).
* [ ] Can be configured favor some formats for feeds over others (e.g., Atom vs. RSS vs. RDF vs. JSONFeed).

### Nice Things

* [X] Supports domain names without typing the `http://` or `https://`.
* [X] Returns a list of results; each contains the feed URI, the format of the feed, and its server media type.
* [ ] Will provide a CLI tool which accepts an input URI and can return a list of feeds.
* [ ] Will support _offline/local_ mode where you can parse a local file, and receive "best-guess" matches.
* [X] Will support caching the results so that the next request for a URI will return the cached results instead of making live queries.
* [X] Supports automatic retries, with exponential back-off + jitter.

### Standards-Compliant

* [X] Code formatting is all [PSR-1], [PSR-2], and [PSR-12]-compliant.
* [X] Supports standardized [PSR-3] loggers like [Monolog](https://packagist.org/packages/monolog/monolog).
* [X] Supports standardized [PSR-4] autoloading for classes and directory structure.
* [X] Code comments and docblocks are compatible with [PSR-5]/[PSR-19].
* [X] Caching will be compatible with [PSR-6]/[PSR-16].
* [X] We leverage [PSR-7] for message handling, and typehint against PSR-7 interface types.

## Development Status

**Pre-1.0 code here. No [SemVer] backwards-compatiblity guaranteed from commit to commit at this stage.**

Most of the important bits are working. Still tweaking the user-facing APIs. Need to refactor a few spots for DRY and just general efficiency. Need to write automated tests. Need to tune the log-levels. Still continues to perform some work after we already have what we need. See the [path to 1.0](https://github.com/simplepie/feed-locator/milestone/1).

We support [PSR-7], but for making the _actual_ requests, we (presently) have a hard dependency on [Guzzle 6 Async Pools](http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=GuzzleHttp\Pool) for purposes of speed and efficiency. Other possible adapters could be accepted once I create a pluggable framework for them.

## Example Usage

```php
use Bramus\Monolog\Formatter\ColoredLineFormatter;
use FeedLocator\FeedLocator;
use FeedLocator\Http\DefaultConfig;
use FeedLocator\Http\Retry;
use GuzzleHttp\Client;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

# Define our logger
$logger  = new Logger('FeedLocator');
$handler = new ErrorLogHandler(
    ErrorLogHandler::OPERATING_SYSTEM,
    LogLevel::DEBUG,
    true,
    false
);
$handler->setFormatter(new ColoredLineFormatter());
$logger->pushHandler($handler);

# Define our HTTP cacher
$tmpDir = sprintf('%s/FeedLocator', sys_get_temp_dir());
$logger->debug(sprintf('Cache directory: %s', $tmpDir));
$cacheMiddleware = new CacheMiddleware(
    new PrivateCacheStrategy(
        new FlysystemStorage(
            new Local($tmpDir)
        )
    )
);

# Discover the feeds linked from Apple's RSS page.
$locator = new FeedLocator('apple.com/rss');

# Use the default configuration, but tweak a few values.
$options = DefaultConfig::clientOptions($logger);
$options['connect_timeout'] = 10.0;
$options['timeout']         = 10.0;
$options['handler']         = DefaultConfig::handlerStack(
    $logger,
    Retry::defaultHandler($logger),
    $cacheMiddleware
);

# Set the logger and Guzzle client to use
$locator->setLogger($logger);
$locator->setGuzzleClient(new Client($options));

# Run, using Guzzle Promises
$pool = $locator->run();
$pool->wait();

# Get the results as an array (from an ArrayIterator)
$results = $locator->getResults()->getArrayCopy();
\print_r($results);
```

Output:

```plain
Array
(
    [0] => Array
        (
            [0] => https://www.apple.com/newsroom/rss-feed.rss
            [1] => atom
            [2] => application/rss+xml;charset=utf-8
        )

    [1] => Array
        (
            [0] => http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/topsongs/limit=10/xml
            [1] => atom
            [2] => application/xml
        )

    [2] => Array
        (
            [0] => http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/topalbums/limit=10/xml
            [1] => atom
            [2] => application/xml
        )

    [3] => Array
        (
            [0] => http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/toppaidapplications/limit=10/xml
            [1] => atom
            [2] => application/xml
        )

    [4] => Array
        (
            [0] => https://developer.apple.com/news/rss/news.rss
            [1] => rss
            [2] => application/rss+xml; charset=UTF-8
        )

    # ...snip...
)
```

## Coding Standards

PSR-1/2/5/12/19 are a solid foundation, but are not an entire coding style by themselves. We automate a large part of our style requirements using [PHP CS Fixer](http://cs.sensiolabs.org) and [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer). (The things that we cannot yet automate are documented in the [SimplePie NG Coding Standards](https://github.com/simplepie/simplepie-ng-coding-standards).)

These can be applied/fixed automatically by running the (lightweight) linter:

```bash
make lint
```

Additionally, in our quest to write excellent code, we use a variety of tools to help us catch issues with what we've written, including:

| Type | Description |
| ---- | ----------- |
| Linting Tools | [PHP CS Fixer](http://cs.sensiolabs.org), [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) |
| QA Tools | [PDepend](https://github.com/pdepend/pdepend), [PHPLOC](https://github.com/sebastianbergmann/phploc), [PHP Copy/Paste Detector](https://github.com/sebastianbergmann/phpcpd), [PHP Code Analyzer](https://github.com/wapmorgan/PhpCodeAnalyzer) |
| Static Analysis | [Phan](https://github.com/phan/phan), [PHPStan](https://github.com/phpstan/phpstan), [Psalm](https://github.com/vimeo/psalm), [PHP Dependency Analysis](https://github.com/mamuz/PhpDependencyAnalysis) |

These reports can be generated by running the (heavyweight) analyzer:

```bash
make analyze
```

## Please Support or Sponsor Development

The SimplePie project is a labor of love. Development of the next-generation of SimplePie was started in June 2017 as because it's a project I love, and I believe our community would benefit from this tool.

If you use SimplePie — especially to make money — it would be swell if you could kick down a few bucks. As the project grows, and we start leveraging more services and architecture, it would be great if it didn't all need to come out of my pocket.

You can also sponsor the development of a particular feature. If there's a feature that you want to see implemented, and I believe it's the right fit for the SimplePie project, you can sponsor the development of the feature to get it prioritized.

Your contributions are greatly and sincerely appreciated. See the **Sponsor** button along the top of the page for more information.

  [PSR-1]: https://www.php-fig.org/psr/psr-1/
  [PSR-2]: https://www.php-fig.org/psr/psr-2/
  [PSR-3]: https://www.php-fig.org/psr/psr-3/
  [PSR-4]: https://www.php-fig.org/psr/psr-4/
  [PSR-5]: https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md
  [PSR-6]: https://www.php-fig.org/psr/psr-6/
  [PSR-7]: https://www.php-fig.org/psr/psr-7/
  [PSR-12]: https://www.php-fig.org/psr/psr-12/
  [PSR-16]: https://www.php-fig.org/psr/psr-16/
  [PSR-19]: https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc-tags.md
  [SemVer]: https://semver.org

