<?php

namespace Weglot\Client\Api\Shared;

/**
 * @phpstan-template T of AbstractCollectionEntry
 */
interface AbstractCollectionInterface
{
    /**
     * Add one word at a time.
     *
     * @phpstan-param T $entry
     *
     * @return $this
     */
    public function addOne(AbstractCollectionEntry $entry);

    /**
     * Add several words at once.
     *
     * @phpstan-param array<T> $entries
     *
     * @return $this
     */
    public function addMany(array $entries);
}
