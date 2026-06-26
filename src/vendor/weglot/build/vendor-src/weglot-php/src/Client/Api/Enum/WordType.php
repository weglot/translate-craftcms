<?php

namespace Weglot\Vendor\Weglot\Client\Api\Enum;

/**
 * Enum WordType
 * Used to define where was the text we are parsing.
 */
abstract class WordType
{
    public const OTHER = 0;
    public const TEXT = 1;
    public const VALUE = 2;
    public const PLACEHOLDER = 3;
    public const META_CONTENT = 4;
    public const IFRAME_SRC = 5;
    public const IMG_SRC = 6;
    public const IMG_ALT = 7;
    public const PDF_HREF = 8;
    public const TITLE = 9;
    public const EXTERNAL_LINK = 10;
    /**
     * Only for internal use, if you have to add a value in this enum,
     * please increments the __MAX value.
     */
    public const __MIN = 0;
    public const __MAX = 10;
}
