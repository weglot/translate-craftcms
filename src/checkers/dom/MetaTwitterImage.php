<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

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
     * @var int
     */
    public const WORD_TYPE = WordType::IMG_SRC;
}
