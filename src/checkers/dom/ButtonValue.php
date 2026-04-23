<?php

declare(strict_types=1);

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class ButtonValue extends AbstractDomChecker
{
    public const DOM = 'button';
    public const PROPERTY = 'value';
    public const WORD_TYPE = WordType::VALUE;
}
