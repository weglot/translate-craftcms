<?php

namespace Weglot\Vendor\Weglot\Client\Api\Shared;

trait AbstractCollectionCountable
{
    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->collection);
    }
}
