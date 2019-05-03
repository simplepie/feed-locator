<?php
/**
 * Copyright (c) 2019 Ryan Parman <http://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Test\Unit\Exception;

use FeedLocator\Exception\FeedLocatorException;
use FeedLocator\Test\Unit\AbstractTestCase;

class FeedLocatorExceptionTest extends AbstractTestCase
{
    public function testThrow(): void
    {
        $this->expectException(\FeedLocator\Exception\FeedLocatorException::class);
        $this->expectExceptionMessage('This is a test message.');

        throw new FeedLocatorException('This is a test message.');
    }
}
