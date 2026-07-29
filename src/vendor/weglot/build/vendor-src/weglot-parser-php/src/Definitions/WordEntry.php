<?php

namespace Weglot\Vendor\Weglot\Parser\Definitions;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Parser\Definitions\Shared\AbstractCollectionEntry;
class WordEntry extends AbstractCollectionEntry
{
    /**
     * @var string
     */
    protected $word;
    /**
     * @var int
     */
    protected $type = WordType::TEXT;
    /**
     * @param string $word
     * @param int    $type
     *
     * @throws InvalidWordTypeException
     */
    public function __construct($word, $type = WordType::TEXT)
    {
        $this->setWord($word)->setType($type);
    }
    /**
     * @param string $word
     *
     * @return $this
     */
    public function setWord($word)
    {
        $this->word = $word;
        return $this;
    }
    /**
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }
    /**
     * @param int $type
     *
     * @return $this
     *
     * @throws InvalidWordTypeException
     */
    public function setType($type)
    {
        if (!($type >= WordType::__MIN && $type <= WordType::__MAX)) {
            throw new InvalidWordTypeException();
        }
        $this->type = $type;
        return $this;
    }
    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function getType()
    {
        return $this->type;
    }
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['t' => $this->getType(), 'w' => $this->getWord()];
    }
}
