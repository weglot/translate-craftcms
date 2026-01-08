<?php

namespace Weglot\Client\Api\Shared;

/**
 * @phpstan-template T of AbstractCollectionEntry
 *
 * @phpstan-implements AbstractCollectionInterface<T>
 * @phpstan-implements \ArrayAccess<int, T>
 * @phpstan-implements \Iterator<int, T>
 */
abstract class AbstractCollection implements \Countable, \Iterator, \ArrayAccess, \JsonSerializable, AbstractCollectionInterface
{
    use AbstractCollectionArrayAccess;
    use AbstractCollectionCountable;
    use AbstractCollectionIterator;
    use AbstractCollectionSerializable;

    /**
     * @phpstan-var array<T>
     */
    protected $collection = [];

    /**
     * @return $this
     */
    public function addOne(AbstractCollectionEntry $entry)
    {
        $this->collection[] = $entry;

        return $this;
    }

    /**
     * @return $this
     */
    public function addMany(array $entries)
    {
        foreach ($entries as $entry) {
            $this->addOne($entry);
        }

        return $this;
    }
}
