<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

class VideoSource extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = 'video source,video';
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'src';
    /**
     * @var int
     */
    public const WORD_TYPE = WordType::IMG_SRC;
}
