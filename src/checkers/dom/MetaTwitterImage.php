<?php

declare(strict_types=1);

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;
use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;

class MetaTwitterImage extends AbstractDomChecker
{
    public const DOM = "meta[name='twitter:image'], meta[name='twitter:image:src']";
    public const PROPERTY = 'content';
    public const WORD_TYPE = WordType::IMG_SRC;
}
