<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class LinkDataValue extends AbstractDomChecker
{
    const DOM = 'a';
    const PROPERTY = 'data-value';
    const WORD_TYPE = WordType::TEXT;
}
