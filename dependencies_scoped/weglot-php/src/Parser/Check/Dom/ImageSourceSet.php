<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class ImageSourceSet extends AbstractDomChecker
{
    const DOM = 'img';
    const PROPERTY = 'srcset';
    const WORD_TYPE = WordType::IMG_SRC;
}
