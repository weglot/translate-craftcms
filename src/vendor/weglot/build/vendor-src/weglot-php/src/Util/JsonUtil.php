<?php

namespace Weglot\Vendor\Weglot\Util;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Client\Api\WordCollection;
use Weglot\Vendor\Weglot\Client\Api\WordEntry;
class JsonUtil
{
    public const SEPARATOR = '##';
    /**
     * @param array<mixed> $data
     * @param string       $key
     *
     * @return mixed
     */
    public static function get(array $data, $key)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }
        return null;
    }
    /**
     * @param string $value
     *
     * @throws InvalidWordTypeException
     */
    public static function add(WordCollection $words, $value): void
    {
        $words->addOne(new WordEntry($value, WordType::TEXT));
    }
    /**
     * @param array<mixed> $data
     * @param string       $index
     * @param int          $nextJson
     *
     * @return array<mixed>
     */
    public static function set(WordCollection $words, $data, $index, &$nextJson)
    {
        $keys = explode(self::SEPARATOR, $index);
        $current =& $data;
        foreach ($keys as $key) {
            if (!\is_array($current) || !\array_key_exists($key, $current)) {
                ++$nextJson;
                return $data;
            }
            $current =& $current[$key];
        }
        if (!isset($words[$nextJson])) {
            ++$nextJson;
            return $data;
        }
        $current = $words[$nextJson]->getWord();
        ++$nextJson;
        return $data;
    }
    /**
     * @param string       $newHTML
     * @param array<mixed> $data
     * @param string       $key
     *
     * @return array<mixed>
     */
    public static function setHTML($newHTML, $data, $key)
    {
        $keys = explode(self::SEPARATOR, $key);
        $current =& $data;
        foreach ($keys as $key) {
            $current =& $current[$key];
        }
        $current = $newHTML;
        return $data;
    }
    /**
     * @param string       $jsonString
     * @param array<mixed> $data
     * @param string       $key
     *
     * @return array<mixed>
     */
    public static function setJSONString($jsonString, $data, $key)
    {
        $keys = explode(self::SEPARATOR, $key);
        $current =& $data;
        foreach ($keys as $key) {
            $current =& $current[$key];
        }
        $current = $jsonString;
        return $data;
    }
}
