<?php

declare(strict_types=1);

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class LinkDataText extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'data-text';

    public const WORD_TYPE = WordType::TEXT;
}
