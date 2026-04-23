<?php

declare(strict_types=1);

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class ImageSource extends AbstractDomChecker
{
    public const DOM = 'img';

    public const PROPERTY = 'src';

    public const WORD_TYPE = WordType::IMG_SRC;
}
