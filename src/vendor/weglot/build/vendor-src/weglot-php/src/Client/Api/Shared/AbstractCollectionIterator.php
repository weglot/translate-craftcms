<?php

namespace Weglot\Vendor\Weglot\Client\Api\Shared;

trait AbstractCollectionIterator
{
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->collection);
    }
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
    public function rewind(): void
    {
        reset($this->collection);
    }
}
