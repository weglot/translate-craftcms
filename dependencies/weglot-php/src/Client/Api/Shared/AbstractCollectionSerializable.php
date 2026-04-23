<?php

declare(strict_types=1);

namespace Weglot\Client\Api\Shared;

trait AbstractCollectionSerializable
{
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $words = [];
        foreach ($this->collection as $entry) {
            $words[] = $entry->jsonSerialize();
        }

        return $words;
    }
}
