<?php

namespace Weglot\Util;

/**
 * Enum SourceType
 * Used to define what the source type is.
 */
abstract class SourceType
{
    public const SOURCE_HTML = 'HTML';
    public const SOURCE_JSON = 'JSON';
    public const SOURCE_TEXT = 'TEXT';
}
