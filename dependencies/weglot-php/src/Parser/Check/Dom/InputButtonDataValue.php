<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class InputButtonDataValue extends AbstractDomChecker
{
    public const DOM = 'input[type="submit"],input[type="button"]';

    public const PROPERTY = 'data-value';

    public const WORD_TYPE = WordType::TEXT;
}
