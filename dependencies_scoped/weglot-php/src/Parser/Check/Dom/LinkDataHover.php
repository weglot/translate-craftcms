<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class LinkDataHover extends AbstractDomChecker
{
    const DOM = 'a';
    const PROPERTY = 'data-hover';
    const WORD_TYPE = WordType::TEXT;
}
