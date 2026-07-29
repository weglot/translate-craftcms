<?php

namespace Weglot\Vendor\Weglot\Parser\Definitions\Shared;

/**
 * @phpstan-template T of AbstractCollectionEntry
 */
interface AbstractCollectionInterface
{
    /**
     * @phpstan-param T $entry
     *
     * @return $this
     */
    public function addOne(AbstractCollectionEntry $entry);
    /**
     * @phpstan-param array<T> $entries
     *
     * @return $this
     */
    public function addMany(array $entries);
}
