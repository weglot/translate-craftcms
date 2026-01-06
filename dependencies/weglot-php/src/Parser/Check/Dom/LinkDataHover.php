<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class LinkDataHover extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'data-hover';

    public const WORD_TYPE = WordType::TEXT;
}
