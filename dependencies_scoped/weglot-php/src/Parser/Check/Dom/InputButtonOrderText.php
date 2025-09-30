<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class InputButtonOrderText extends AbstractDomChecker
{
    const DOM = 'input[type="submit"],input[type="button"]';
    const PROPERTY = 'data-order_button_text';
    const WORD_TYPE = WordType::TEXT;
}
