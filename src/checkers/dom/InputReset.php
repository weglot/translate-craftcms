<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

class InputReset extends AbstractDomChecker
{
    public const DOM = "input[type='reset']";
    public const PROPERTY = 'value';
    public const WORD_TYPE = WordType::TEXT;
}
