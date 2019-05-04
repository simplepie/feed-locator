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
 * The copyright header above is automatically generated. Corrected copyright header is as follows:.
 *
 * This file was forked from https://github.com/alexeyshockov/guzzle-dynamic-pool/blob/master/src/ExpectingIterator.php
 *
 * Original code, copyright (c) 2019 Alexey Shokov <http://alexey.shockov.com>.
 * Adaptations, copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Additional adaptations, copyright (c) 2019 Contributors.
 */

class ExpectingIterator implements Iterator
{
    /**
     * Internal iterator.
     *
     * @var Iterator
     */
    private $inner;

    /**
     * The state of the previous state's validity.
     *
     * @var bool
     */
    private $wasValid;

    /**
     * Constructs a new instance of this class.
     *
     * @param Iterator $inner The iterable to wrap.
     */
    public function __construct(Iterator $inner)
    {
        $this->inner = $inner;
    }

    /**
     * Move internal pointer forward to next element.
     */
    public function next(): void
    {
        if (!$this->wasValid && $this->valid()) {
            // Just do nothing, because the inner iterator has became valid
        } else {
            $this->inner->next();
        }

        $this->wasValid = $this->valid();
    }

    /**
     * Return the current element from the internal pointer.
     *
     * @return mixed The return value from the callable.
     */
    public function current()
    {
        return $this->inner->current();
    }

    /**
     * Rewind the internal pointer to the first element.
     */
    public function rewind(): void
    {
        $this->inner->rewind();

        $this->wasValid = $this->valid();
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
