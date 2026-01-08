<?php

namespace Weglot\Util;

class Text
{
    /**
     * @param string $word
     *
     * @return string
     */
    public static function fullTrim($word)
    {
        return trim($word, " \t\n\r\0\x0B\xA0�");
    }

    /**
     * @param string $haystack
     * @param string $search
     *
     * @return bool
     */
    public static function contains($haystack, $search)
    {
        if (\is_string($haystack) && str_contains($haystack, $search)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string|null $filename
     *
     * @return string
     */
    public static function removeFileExtension($filename)
    {
        $filename = null !== $filename ? $filename : '';

        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
    }

    /**
     * @param string|null $regex
     *
     * @return string
     */
    public static function escapeForRegex($regex)
    {
        if (null !== $regex) {
            return str_replace('\\\\/', '\/', str_replace('/', '\/', $regex));
        } else {
            return str_replace('\\\\/', '\/', str_replace('/', '\/', ''));
        }
    }

    /**
     * @param mixed $string
     *
     * @return bool
     */
    public static function isJSON($string)
    {
        if (!\is_string($string) || empty($string)) {
            return false;
        }
        json_decode($string);

        return \JSON_ERROR_NONE == json_last_error() && \in_array(substr($string, 0, 1), ['{', '[']);
    }

    /**
     * @param mixed $string
     *
     * @return bool
     */
    public static function isHTML($string)
    {
        if (\is_string($string)) {
            return strip_tags($string) !== $string;
        } else {
            return false;
        }
    }
}
