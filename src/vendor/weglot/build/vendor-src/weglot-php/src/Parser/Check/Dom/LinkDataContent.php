<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class LinkDataContent extends AbstractDomChecker
{
    const DOM = 'a';
    const PROPERTY = 'data-content';
    const WORD_TYPE = WordType::TEXT;
}
