<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class ButtonValue extends AbstractDomChecker
{
    /**
     * {@inheritdoc}
     */
    public const DOM = 'button';

    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'value';

    /**
     * @var int
     */
    public const WORD_TYPE = WordType::VALUE;
}
