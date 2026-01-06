<?php

namespace Weglot\Parser\Formatter;

use Weglot\Client\Api\TranslateEntry;
use Weglot\Parser\Parser;

abstract class AbstractFormatter
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var TranslateEntry
     */
    protected $translated;

    public function __construct(Parser $parser, TranslateEntry $translated)
    {
        $this
            ->setParser($parser)
            ->setTranslated($translated);
    }

    /**
     * @return $this
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return $this
     */
    public function setTranslated(TranslateEntry $translated)
    {
        $this->translated = $translated;

        return $this;
    }

    /**
     * @return TranslateEntry
     */
    public function getTranslated()
    {
        return $this->translated;
    }

    /**
     * @param int $index
     *
     * @return void
     */
    abstract public function handle(array $array, &$index);
}
