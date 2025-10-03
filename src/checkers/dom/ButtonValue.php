<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

class ButtonValue extends AbstractDomChecker
{
    public const DOM = 'button';
    public const PROPERTY = 'value';
    public const WORD_TYPE = WordType::VALUE;
}
