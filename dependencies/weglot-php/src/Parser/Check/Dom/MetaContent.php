<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Util\Text as TextUtil;

class MetaContent extends AbstractDomChecker
{
    public const DOM = 'meta[name="description"],meta[property="og:description"],meta[property="og:site_name"],meta[name="twitter:description"]';

    public const PROPERTY = 'content';

    public const WORD_TYPE = WordType::META_CONTENT;

    public const ESCAPE_SPECIAL_CHAR = true;

    protected function check()
    {
        return !is_numeric(TextUtil::fullTrim($this->node->placeholder))
            && !preg_match('/^\d+%$/', TextUtil::fullTrim($this->node->placeholder));
    }
}
