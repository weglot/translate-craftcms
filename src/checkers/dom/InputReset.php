<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

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
     * @var int
     */
    public const WORD_TYPE = WordType::TEXT;
}
