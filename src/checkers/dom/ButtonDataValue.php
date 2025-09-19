<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

/**
 * @since 2.0.6
 */
class ButtonDataValue extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = 'button';
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'data-value';
    /**
     * @var int
     */
    public const WORD_TYPE = WordType::VALUE;
}
