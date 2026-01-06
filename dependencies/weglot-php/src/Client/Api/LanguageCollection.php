<?php

namespace Weglot\Client\Api;

use Weglot\Client\Api\Shared\AbstractCollection;
use Weglot\Client\Api\Shared\AbstractCollectionEntry;

/**
 * @phpstan-extends AbstractCollection<LanguageEntry>
 */
class LanguageCollection extends AbstractCollection
{
    /**
     * @return $this
     */
    public function addOne(AbstractCollectionEntry $entry)
    {
        $this->collection[$entry->getInternalCode()] = $entry;

        return $this;
    }

    /**
     * @param string $iso639 ISO 639-1 code to identify language
     *
     * @return LanguageEntry|null
     */
    public function getCode($iso639)
    {
        if (isset($this->collection[$iso639])) {
            return $this->collection[$iso639];
        }

        return null;
    }
}
