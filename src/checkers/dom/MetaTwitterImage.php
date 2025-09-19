<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

/**
 * @since 2.5.0
 */
class MetaTwitterImage extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = "meta[name='twitter:image'], meta[name='twitter:image:src']";
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'content';
    /**
     * @var integer
     */
    public const WORD_TYPE = WordType::IMG_SRC;
}
