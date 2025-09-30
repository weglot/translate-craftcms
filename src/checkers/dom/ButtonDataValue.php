<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class ButtonDataValue extends AbstractDomChecker
{
    public const DOM = 'button';

    public const PROPERTY = 'data-value';
    /**
     * @var int
     */
    public const WORD_TYPE = WordType::VALUE;
}
