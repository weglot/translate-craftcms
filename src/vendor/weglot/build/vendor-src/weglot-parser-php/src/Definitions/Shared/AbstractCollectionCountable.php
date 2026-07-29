<?php

namespace Weglot\Vendor\Weglot\Parser\Definitions\Shared;

trait AbstractCollectionCountable
{
    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->collection);
    }
}
