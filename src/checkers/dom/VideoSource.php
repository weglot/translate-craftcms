<?php

declare(strict_types=1);

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;
use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;

class VideoSource extends AbstractDomChecker
{
    public const DOM = 'video source,video';
    public const PROPERTY = 'src';
    public const WORD_TYPE = WordType::IMG_SRC;
}
