<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class ImageDataSource extends AbstractDomChecker
{
    const DOM = 'img';
    const PROPERTY = 'data-src';
    const WORD_TYPE = WordType::IMG_SRC;
}
