<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Parser\Check\Dom\AbstractDomChecker;

/**
 * @since 2.5.0
 */
class InputReset extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = "input[type='reset']";
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'value';
    /**
     * @var integer
     */
    public const WORD_TYPE = WordType::TEXT;
}
