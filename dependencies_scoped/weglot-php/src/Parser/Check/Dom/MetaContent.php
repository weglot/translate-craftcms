<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Util\Text as TextUtil;
class MetaContent extends AbstractDomChecker
{
    const DOM = 'meta[name="description"],meta[property="og:description"],meta[property="og:site_name"],meta[name="twitter:description"]';
    const PROPERTY = 'content';
    const WORD_TYPE = WordType::META_CONTENT;
    const ESCAPE_SPECIAL_CHAR = \true;
    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->placeholder)) && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->placeholder));
    }
}
