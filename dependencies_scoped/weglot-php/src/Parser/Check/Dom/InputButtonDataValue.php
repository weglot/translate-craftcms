<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class InputButtonDataValue extends AbstractDomChecker
{
    const DOM = 'input[type="submit"],input[type="button"]';
    const PROPERTY = 'data-value';
    const WORD_TYPE = WordType::TEXT;
}
