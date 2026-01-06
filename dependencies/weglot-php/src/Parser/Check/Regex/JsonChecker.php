<?php

namespace Weglot\Parser\Check\Regex;

use Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Parser\Parser;
use Weglot\Util\JsonUtil;
use Weglot\Util\SourceType;
use Weglot\Util\Text;

class JsonChecker
{
    /**
     * @var string[]
     */
    protected $default_keys = ['description', 'name'];

    /**
     * @var string
     */
    protected $jsonString;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $extraKeys;

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
     * @param string $jsonString
     * @param array  $extraKeys
     */
    public function __construct(Parser $parser, $jsonString, $extraKeys)
    {
        $this
            ->setParser($parser)
            ->setJSonString($jsonString)
            ->setExtraKeys($extraKeys);
    }

    /**
     * @param string $jsonString
     *
     * @return $this
     */
    public function setJsonString($jsonString)
    {
        $this->jsonString = $jsonString;

        return $this;
    }

    /**
     * @return string
     */
    public function getJsonString()
    {
        return $this->jsonString;
    }

    /**
     * @param array $extraKeys
     *
     * @return $this
     */
    public function setExtraKeys($extraKeys)
    {
        $this->extraKeys = $extraKeys;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtraKeys()
    {
        return $this->extraKeys;
    }

    /**
     * @return array
     *
     * @throws InvalidWordTypeException
     */
    public function handle()
    {
        $json = json_decode($this->jsonString, true);

        $paths = [];
        if (\is_array($json)) {
            $this->findWords($json, '', $paths);
        }

        return [
            'type' => SourceType::SOURCE_JSON,
            'source' => $this->jsonString,
            'jsonArray' => $json,
            'paths' => $paths,
        ];
    }

    /**
     * @param array<mixed>                                 $json
     * @param string                                       $currentKey
     * @param array<array{key: int|string, parsed: array}> $paths
     *
     * @return void
     *
     * @throws InvalidWordTypeException
     */
    public function findWords($json, $currentKey, &$paths)
    {
        foreach ($json as $key => $value) {
            if (!\is_string($value)) {
                if (\is_array($value)) {
                    $this->findWords($value, ltrim($currentKey.JsonUtil::SEPARATOR.$key, JsonUtil::SEPARATOR), $paths);
                }
                continue;
            }

            $k = ltrim($currentKey.JsonUtil::SEPARATOR.$key, JsonUtil::SEPARATOR);
            if (Text::isJSON($value)) {
                $parsed = $this->getParser()->parseJSON($value, $this->getExtraKeys());
            } elseif (Text::isHTML($value)) {
                $parsed = $this->getParser()->parseHTML($value);
            } elseif (
                (!\is_int($key) && \in_array($key, array_unique(array_merge($this->default_keys, $this->getExtraKeys())), true))
                || (\is_int($key) && \in_array(substr($currentKey, (strrpos($currentKey, JsonUtil::SEPARATOR) ?: -\strlen(JsonUtil::SEPARATOR)) + \strlen(JsonUtil::SEPARATOR)), array_unique(array_merge($this->default_keys, $this->getExtraKeys())), true))
            ) {
                $parsed = $this->getParser()->parseText($value);
            } else {
                continue;
            }

            $paths[] = ['key' => $k, 'parsed' => $parsed];
        }
    }
}
