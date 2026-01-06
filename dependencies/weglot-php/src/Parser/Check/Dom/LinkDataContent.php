<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class LinkDataContent extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'data-content';

    public const WORD_TYPE = WordType::TEXT;
}
