<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Enum;

use SimplePie\UtilityPack\Enum\AbstractEnum;

/**
 * Provides a set of known, allowable feed types.
 */
class FeedFormat extends AbstractEnum
{
    public const ANY = 'any';

    public const JSON = 'json';

    public const XML = 'xml';

    public const JSONFEED = 'jsonfeed';

    public const RDF = 'rdf';

    public const RSS = 'rss';

    public const ATOM = 'atom';
}
