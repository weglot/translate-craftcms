<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class MetaTwitter extends AbstractDomChecker
{
    public const DOM = "meta[name='twitter:card'],meta[name='twitter:site'],meta[name='twitter:creator']";
    public const PROPERTY = 'content';
    public const WORD_TYPE = WordType::META_CONTENT;
}
