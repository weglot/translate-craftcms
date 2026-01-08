<?php

namespace Weglot\Client\Api\Shared;

trait AbstractCollectionCountable
{
    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->collection);
    }
}
