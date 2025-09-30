<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class SpanTitle extends AbstractDomChecker
{
    const DOM = 'span[title]';
    const PROPERTY = 'title';
    const WORD_TYPE = WordType::TEXT;
}
