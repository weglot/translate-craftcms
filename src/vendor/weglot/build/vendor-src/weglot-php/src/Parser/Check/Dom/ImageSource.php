<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class ImageSource extends AbstractDomChecker
{
    const DOM = 'img';
    const PROPERTY = 'src';
    const WORD_TYPE = WordType::IMG_SRC;
}
