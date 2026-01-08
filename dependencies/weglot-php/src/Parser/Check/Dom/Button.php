<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Util\Text as TextUtil;

class Button extends AbstractDomChecker
{
    public const DOM = 'input[type="submit"],input[type="button"],button';

    public const PROPERTY = 'value';

    public const WORD_TYPE = WordType::VALUE;

    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->value))
            && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->value));
    }
}
