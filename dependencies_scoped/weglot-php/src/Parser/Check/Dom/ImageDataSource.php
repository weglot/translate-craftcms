<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;

class ImageDataSource extends AbstractDomChecker
{
    public const DOM = 'img';
    public const PROPERTY = 'data-src';
    public const WORD_TYPE = WordType::IMG_SRC;
}
