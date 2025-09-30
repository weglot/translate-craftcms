<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Util\Text as TextUtil;
class MetaTitleContent extends AbstractDomChecker
{
    const DOM = 'meta[property="og:title"],meta[name="twitter:title"]';
    const PROPERTY = 'content';
    const WORD_TYPE = WordType::TITLE;
    const ESCAPE_SPECIAL_CHAR = \true;
    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->placeholder)) && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->placeholder));
    }
}
