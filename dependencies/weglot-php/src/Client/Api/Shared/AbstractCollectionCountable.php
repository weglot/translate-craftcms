<?php

declare(strict_types=1);

namespace Weglot\Client\Api\Shared;

trait AbstractCollectionCountable
{
    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->collection);
    }
}
