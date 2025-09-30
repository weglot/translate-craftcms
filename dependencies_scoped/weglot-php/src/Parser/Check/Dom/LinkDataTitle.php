<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class LinkDataTitle extends AbstractDomChecker
{
    public const DOM = 'a';
    public const PROPERTY = 'data-title';
    public const WORD_TYPE = WordType::TEXT;
    public const ESCAPE_SPECIAL_CHAR = \true;
}
