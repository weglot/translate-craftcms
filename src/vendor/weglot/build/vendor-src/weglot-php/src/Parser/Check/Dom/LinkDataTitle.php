<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class LinkDataTitle extends AbstractDomChecker
{
    const DOM = 'a';
    const PROPERTY = 'data-title';
    const WORD_TYPE = WordType::TEXT;
    const ESCAPE_SPECIAL_CHAR = \true;
}
