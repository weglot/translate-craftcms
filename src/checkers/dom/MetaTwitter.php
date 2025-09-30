<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

/**
 * @since 2.0
 */
class MetaTwitter extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = "meta[name='twitter:card'],meta[name='twitter:site'],meta[name='twitter:creator']";
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'content';
    /**
     * @var int
     */
    public const WORD_TYPE = WordType::META_CONTENT;
}
