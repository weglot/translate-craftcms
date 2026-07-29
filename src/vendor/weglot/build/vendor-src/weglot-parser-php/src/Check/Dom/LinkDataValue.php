<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
class LinkDataValue extends AbstractDomChecker
{
    public const DOM = 'a';
    public const PROPERTY = 'data-value';
    public const WORD_TYPE = WordType::TEXT;
}
