<?php

namespace Weglot\Vendor\Weglot\Util\Regex;

abstract class RegexEnum
{
    /**
     * @var string
     */
    const START_WITH = 'START_WITH';
    /**
     * @var string
     */
    const NOT_START_WITH = 'NOT_START_WITH';
    /**
     * @var string
     */
    const END_WITH = 'END_WITH';
    /**
     * @var string
     */
    const NOT_END_WITH = 'NOT_END_WITH';
    /**
     * @var string
     */
    const CONTAIN = 'CONTAIN';
    /**
     * @var string
     */
    const NOT_CONTAIN = 'NOT_CONTAIN';
    /**
     * @var string
     */
    const IS_EXACTLY = 'IS_EXACTLY';
    /**
     * @var string
     */
    const NOT_IS_EXACTLY = 'NOT_IS_EXACTLY';
    /**
     * @var string
     */
    const MATCH_REGEX = 'MATCH_REGEX';
}
