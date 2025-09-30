<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class MetaTwitterImage extends AbstractDomChecker
{
    public const DOM = "meta[name='twitter:image'], meta[name='twitter:image:src']";
    public const PROPERTY = 'content';
    public const WORD_TYPE = WordType::IMG_SRC;
}
