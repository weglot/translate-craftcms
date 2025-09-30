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
        return $this->collection[$offset] ?? null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (isset($this->collection[$offset]) && $value instanceof AbstractCollectionEntry) {
            $this->collection[$offset] = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    }
}
