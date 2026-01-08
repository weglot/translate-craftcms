<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Util\Text as TextUtil;

class MetaTitleContent extends AbstractDomChecker
{
    public const DOM = 'meta[property="og:title"],meta[name="twitter:title"]';

    public const PROPERTY = 'content';

    public const WORD_TYPE = WordType::TITLE;

    public const ESCAPE_SPECIAL_CHAR = true;

    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->placeholder))
            && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->placeholder));
    }
}
