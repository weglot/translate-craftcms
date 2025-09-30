<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class InputRadioOrderText extends AbstractDomChecker
{
    public const DOM = 'input[type="radio"]';
    public const PROPERTY = 'data-order_button_text';
    public const WORD_TYPE = WordType::VALUE;
}
