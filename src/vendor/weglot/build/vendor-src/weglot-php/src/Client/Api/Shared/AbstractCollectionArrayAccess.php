<?php

namespace Weglot\Vendor\Weglot\Client\Api\Shared;

trait AbstractCollectionArrayAccess
{
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->collection[$offset]);
    }
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
    }
    public function offsetSet($offset, $value): void
    {
        if (isset($this->collection[$offset]) && $value instanceof AbstractCollectionEntry) {
            $this->collection[$offset] = $value;
        }
    }
    public function offsetUnset($offset): void
    {
        unset($this->collection[$offset]);
    }
}
