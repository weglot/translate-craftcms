<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

/**
 * @since 2.5.0
 */
class MetaFacebookImage extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = "meta[property='og:image'], meta[property='og:image:secure_url']";
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'content';
    /**
     * @var int
     */
    public const WORD_TYPE = WordType::IMG_SRC;
}
