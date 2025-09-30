<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Util\Text as TextUtil;
class Placeholder extends AbstractDomChecker
{
    const DOM = 'input[type="text"],input[type="password"],input[type="search"],input[type="email"],textarea, input[type="tel"], input[type="number"]';
    const PROPERTY = 'placeholder';
    const WORD_TYPE = WordType::PLACEHOLDER;
    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->placeholder)) && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->placeholder));
    }
}
