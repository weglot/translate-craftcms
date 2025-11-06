<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class LinkDataText extends AbstractDomChecker
{
    const DOM = 'a';
    const PROPERTY = 'data-text';
    const WORD_TYPE = WordType::TEXT;
}
