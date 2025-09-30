<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class TdDataTitle extends AbstractDomChecker
{
    public const DOM = 'td';
    public const PROPERTY = 'data-title';
    public const WORD_TYPE = WordType::VALUE;
}
