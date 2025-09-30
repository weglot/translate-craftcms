<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class InputButtonDataValue extends AbstractDomChecker
{
    public const DOM = 'input[type="submit"],input[type="button"]';
    public const PROPERTY = 'data-value';
    public const WORD_TYPE = WordType::TEXT;
}
