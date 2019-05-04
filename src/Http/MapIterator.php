<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Http;

use Iterator;

/*
 * The copyright header above is automatically generated. Corrected copyright header is as follows:
 *
 * This file was forked from https://github.com/alexeyshockov/guzzle-dynamic-pool/blob/master/src/MapIterator.php
 *
 * Original code, copyright (c) 2019 Alexey Shokov <http://alexey.shockov.com>.
 * Adaptations, copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Additional adaptations, copyright (c) 2019 Contributors.
 */

class MapIterator implements Iterator
{
    /**
     * Internal iterator.
     *
     * @var Iterator
     */
    private $inner;

    /**
     * The event handler as a callable.
     *
     * @var callable
     */
    private $handler;

    /**
     * Constructs a new instance of this class.
     *
     * @param Iterator $inner   The iterable to wrap.
     * @param callable $handler The event handler as a callable.
     */
    public function __construct(Iterator $inner, callable $handler)
    {
        $this->inner   = $inner;
        $this->handler = $handler;
    }

    /**
     * Move internal pointer forward to next element.
     */
    public function next(): void
    {
        $this->inner->next();
    }

    /**
     * Return the current element from the internal pointer.
     *
     * @return mixed The return value from the callable.
     */
    public function current()
    {
        return ($this->handler)($this->inner->current(), $this->inner);
    }

    /**
     * Rewind the internal pointer to the first element.
     */
    public function rewind(): void
    {
        $this->inner->rewind();
    }

    /**
     * Return the key of the current element.
     */
    public function key(): int
    {
        return $this->inner->key();
    }

    /**
     * Checks if current position of the internal pointer is valid.
     */
    public function valid(): bool
    {
        return $this->inner->valid();
    }
}
