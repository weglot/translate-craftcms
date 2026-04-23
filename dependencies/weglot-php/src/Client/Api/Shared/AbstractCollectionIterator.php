<?php

declare(strict_types=1);

namespace Weglot\Client\Api\Shared;

trait AbstractCollectionIterator
{
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->collection);
    }

    #[\ReturnTypeWillChange]
    public function next(): void
    {
        next($this->collection);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->collection);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return null !== key($this->collection);
    }

    #[\ReturnTypeWillChange]
    public function rewind(): void
    {
        reset($this->collection);
    }
}
