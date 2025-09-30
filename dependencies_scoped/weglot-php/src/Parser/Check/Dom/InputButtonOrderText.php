<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class InputButtonOrderText extends AbstractDomChecker
{
    public const DOM = 'input[type="submit"],input[type="button"]';
    public const PROPERTY = 'data-order_button_text';
    public const WORD_TYPE = WordType::TEXT;
}
