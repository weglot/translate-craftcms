<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class ImageAlt extends AbstractDomChecker
{
    public const DOM = 'img';

    public const PROPERTY = 'alt';

    public const WORD_TYPE = WordType::IMG_ALT;

    public const ESCAPE_SPECIAL_CHAR = true;
}
