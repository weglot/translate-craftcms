<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class VideoSource extends AbstractDomChecker
{
    public const DOM = 'video source,video';
    public const PROPERTY = 'src';
    public const WORD_TYPE = WordType::IMG_SRC;
}
