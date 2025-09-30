<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class ImageSourceSet extends AbstractDomChecker
{
    public const DOM = 'img';
    public const PROPERTY = 'srcset';
    public const WORD_TYPE = WordType::IMG_SRC;
}
