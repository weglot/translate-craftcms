<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
class ImageSource extends AbstractDomChecker
{
    public const DOM = 'img';
    public const PROPERTY = 'src';
    public const WORD_TYPE = WordType::IMG_SRC;
}
