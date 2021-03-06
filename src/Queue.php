<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator;

use ArrayAccess;
use ArrayIterator;
use Countable;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriNormalizer;
use Iterator;
use Serializable;

class Queue implements ArrayAccess, Countable, Iterator, Serializable
{
    /**
     * The queue object which holds references to the work tasks which still need to be performed.
     *
     * @var ArrayIterator
     */
    protected $queue;

    /**
     * The queue object which holds references to the work tasks which still need to be performed.
     *
     * @var ArrayIterator
     */
    protected $completed;

    /**
     * The callable to apply to every new entry in the queue.
     *
     * @var callable
     */
    protected $func;

    /**
     * Constructs a new instance of this class.
     *
     * @param array|iterable $values [description]
     * @param callable|null  $func   [description]
     */
    public function __construct(iterable $values = [], ?callable $func = null)
    {
        $this->func = $func ?: static function (string $uri): string {
            return (string) UriNormalizer::normalize(new Uri($uri));
        };

        $this->completed = new ArrayIterator();
        $this->queue     = new ArrayIterator([], ArrayIterator::STD_PROP_LIST | ArrayIterator::ARRAY_AS_PROPS);

        foreach ($values as $value) {
            $value               = ($this->func)($value);
            $this->queue[$value] = $value;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        return isset($this->queue[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        if (isset($this->queue[$offset])) {
            $value                   = $this->queue[$offset];
            $this->completed[$value] = null;
            unset($this->queue[$offset]);

            return $value;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $values): void
    {
        // Shutup linter
        $offset;

        $values = \is_iterable($values)
            ? $values
            : [$values];

        foreach ($values as $value) {
            // Normalize
            $value = ($this->func)($value);

            if (!isset($this->completed[$value])) {
                $this->queue[$value] = $value;
            }
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset): void
    {
        unset($this->queue[$offset]);
    }

    /**
     * @see Countable
     */
    public function count(): int
    {
        return \count($this->queue);
    }

    /**
     * @see Serializable
     */
    public function serialize(): string
    {
        return \serialize([
            'queue'     => $this->queue,
            'completed' => $this->completed,
        ]);
    }

    /**
     * @see Serializable
     */
    public function unserialize($serialized): void
    {
        $data = \unserialize($serialized);

        $this->queue     = $data['queue'];
        $this->completed = $data['completed'];
    }

    /**
     * @see SeekableIterator
     */
    public function current()
    {
        return $this->queue->current();
    }

    /**
     * @see SeekableIterator
     */
    public function key()
    {
        return $this->queue->key();
    }

    /**
     * @see SeekableIterator
     */
    public function next(): void
    {
        $this->queue->next();
    }

    /**
     * @see SeekableIterator
     */
    public function rewind(): void
    {
        $this->queue->rewind();
    }

    /**
     * @see SeekableIterator
     */
    public function valid(): bool
    {
        return $this->queue->valid();
    }

    /**
     * Simplified access to a copy of the array.
     */
    public function getArrayCopy(): array
    {
        return $this->queue->getArrayCopy();
    }

    /**
     * Implementation of array.push.
     */
    public function push($value): void
    {
        $this->offsetSet($value, $value);
    }

    /**
     * Implementation of array.push.
     */
    public function append($value): void
    {
        $this->offsetSet($value, $value);
    }

    /**
     * Implementation of array.pop.
     */
    public function pop()
    {
        $queue = $this->queue->getArrayCopy();
        $value = \array_pop($queue);

        $this->queue             = new ArrayIterator($queue);
        $this->completed[$value] = $value;

        return $value;
    }
}
