<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Util\Text as TextUtil;

class Placeholder extends AbstractDomChecker
{
    public const DOM = 'input[type="text"],input[type="password"],input[type="search"],input[type="email"],textarea, input[type="tel"], input[type="number"]';

    public const PROPERTY = 'placeholder';

    public const WORD_TYPE = WordType::PLACEHOLDER;

    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->placeholder))
            && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->placeholder));
    }
}
