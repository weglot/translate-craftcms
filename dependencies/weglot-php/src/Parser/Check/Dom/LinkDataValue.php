<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class LinkDataValue extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'data-value';

    public const WORD_TYPE = WordType::TEXT;
}
