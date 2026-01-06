<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class LinkDataTooltip extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'data-tooltip';

    public const WORD_TYPE = WordType::TEXT;
}
