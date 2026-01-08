<?php

namespace Weglot\Parser\Formatter;

use Weglot\Client\Api\TranslateEntry;
use Weglot\Parser\Parser;
use Weglot\Util\JsonUtil;
use Weglot\Util\SourceType;

class JsonFormatter extends AbstractFormatter
{
    /**
     * @var string
     */
    protected $source;

    /**
     * @param string $source
     */
    public function __construct(Parser $parser, $source, TranslateEntry $translated)
    {
        $this->setSource($source);
        parent::__construct($parser, $translated);
    }

    /**
     * @param string $source
     *
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    public function handle(array $tree, &$index)
    {
        $translated_words = $this->getTranslated()->getOutputWords();

        $jsonString = $tree['source'];
        $jsonArray = $tree['jsonArray'];
        $paths = $tree['paths'];

        foreach ($paths as $path) {
            $key = $path['key'];
            $parsed = $path['parsed'];

            if (SourceType::SOURCE_TEXT === $parsed['type']) {
                $jsonArray = JsonUtil::set($translated_words, $jsonArray, $key, $index);
            }
            if (SourceType::SOURCE_JSON === $parsed['type']) {
                $source = $this->getParser()->formatters($parsed['source'], $this->getTranslated(), $parsed, $index);
                $jsonArray = JsonUtil::setJSONString($source, $jsonArray, $key);
            }
            if (SourceType::SOURCE_HTML === $parsed['type']) {
                if ($parsed['nodes']) {
                    $formatter = new DomFormatter($this->getParser(), $this->getTranslated());
                    $formatter->handle($parsed['nodes'], $index);
                    $jsonArray = JsonUtil::setHTML($parsed['dom']->save(), $jsonArray, $key);

                    foreach ($parsed['regexes'] as $regex) {
                        $translatedRegex = $this->getParser()->formatters($regex['source'], $this->getTranslated(), $regex, $index);
                        $source = str_replace($regex['source'], $translatedRegex, $parsed['source']);
                    }
                }
            }
        }

        $this->setSource(str_replace($jsonString, json_encode($jsonArray), $this->getSource()));
    }
}
