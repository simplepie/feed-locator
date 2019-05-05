<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Enum;

/**
 * Provides a set of known, allowable feed types.
 */
class FeedType extends AbstractEnum
{
    public const ALL = 'all';

    public const JSON = 'json';

    public const HTML = 'html';

    public const XML = 'xml';
}
