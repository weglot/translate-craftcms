<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
class LinkDataHover extends AbstractDomChecker
{
    public const DOM = 'a';
    public const PROPERTY = 'data-hover';
    public const WORD_TYPE = WordType::TEXT;
}
