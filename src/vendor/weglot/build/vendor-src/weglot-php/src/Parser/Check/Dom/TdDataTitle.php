<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class TdDataTitle extends AbstractDomChecker
{
    const DOM = 'td';
    const PROPERTY = 'data-title';
    const WORD_TYPE = WordType::VALUE;
}
