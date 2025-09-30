<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class SpanTitle extends AbstractDomChecker
{
    public const DOM = 'span[title]';
    public const PROPERTY = 'title';
    public const WORD_TYPE = WordType::TEXT;
}
