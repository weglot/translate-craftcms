<?php

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
