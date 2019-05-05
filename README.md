# Feed Locator

**Don't use this yet.**

* https://github.com/simplepie/simplepie/blob/master/library/SimplePie/Locator.php
* https://web.archive.org/web/20100620085023/http://diveintomark.org/archives/2002/08/15/ultraliberal_rss_locator

## Development Notes

### PSR-7

We leverage PSR-7 as much as possible for message handling, and typehint against PSR-7 interface types.

But for making the _actual_ requests, we (presently) have a hard dependency on [Guzzle 6 Async Pools](http://docs.guzzlephp.org/en/stable/quickstart.html?highlight=GuzzleHttp\Pool) for purposes of speed and efficiency. I'm open to adding support for other adapters, but haven't done that yet. Other possible adapters could be accepted once I create a pluggable framework for them.

* <https://github.com/guzzle/guzzle>
* <https://github.com/illuminate/http>
* <https://book.cakephp.org/3.0/en/core-libraries/httpclient.html>
* <https://github.com/php-http/httplug>
* <https://github.com/kriswallsmith/Buzz>
* <https://github.com/symfony/http-client>
* <https://github.com/zendframework/zend-psr7bridge>
* <https://github.com/clue/reactphp-buzz>

### Content Sniffing

Servers lie. When it comes to feeds, content types should be viewed as _suggestions_, but can't be trusted as _canonical_. While specification enthusiasts may not approve, real-world usability says that we need to perform _content sniffing_. I'm aware of the potential security hazards, but show me a better implementation and I'll take a look at it.

When _content sniffing_ is disabled, we fall back to relying on server-provided content-types. This tends to be more brittle (see “servers lie”), but _may_ be slightly faster. We compare against a known-good set of media types. In practice, relying on content-type headers tends to result in both false-positives and false-negatives.

| Format | Media Types |
| ------ | ----------- |
| Atom | `application/atom+xml`, `application/xml`, `text/xml` |
| JSONFeed | `application/feed+json`¹, `application/json` |
| RSS | `application/rss+xml`, `application/xml`, `text/xml` |
| RDF (RSS 1.0) | `application/rdf+xml`, `application/rdf`, `application/xml`, `text/xml`, `text/rdf` |

* ¹ <https://github.com/brentsimmons/JSONFeed/pull/32>


## Coding Standards

PSR-1/2/12 are a solid foundation, but are not an entire coding style by themselves. By leveraging tools such as [PHP CS Fixer](http://cs.sensiolabs.org) and [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer), we can automate a large part of our style requirements. The things that we cannot yet automate are documented here:

<https://github.com/simplepie/simplepie-ng-coding-standards>

## Please Support or Sponsor Development

[![Beerpay](https://img.shields.io/beerpay/simplepie/simplepie-ng.svg?style=flat-square)](https://beerpay.io/simplepie/simplepie-ng)

SimplePie NG is a labor of love. I have been working on it in my free time since June 2017 because it's a project I love, and I believe our community would benefit from this tool.

If you use SimplePie NG — especially to make money — it would be swell if you could kick down a few bucks. As the project grows, and we start leveraging more services and architecture, it would be great if it didn't all need to come out of my pocket.

You can also sponsor the development of a particular feature. If there's a feature that you want to see implemented, and I believe it's the right fit for SimplePie NG, you can sponsor the development of the feature to get it prioritized.

Your contributions are greatly and sincerely appreciated.
