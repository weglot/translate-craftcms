<?php

namespace Weglot\Util\Regex;

abstract class RegexEnum
{
    /**
     * @var string
     */
    public const START_WITH = 'START_WITH';

    /**
     * @var string
     */
    public const NOT_START_WITH = 'NOT_START_WITH';

    /**
     * @var string
     */
    public const END_WITH = 'END_WITH';

    /**
     * @var string
     */
    public const NOT_END_WITH = 'NOT_END_WITH';

    /**
     * @var string
     */
    public const CONTAIN = 'CONTAIN';

    /**
     * @var string
     */
    public const NOT_CONTAIN = 'NOT_CONTAIN';

    /**
     * @var string
     */
    public const IS_EXACTLY = 'IS_EXACTLY';

    /**
     * @var string
     */
    public const NOT_IS_EXACTLY = 'NOT_IS_EXACTLY';

    /**
     * @var string
     */
    public const MATCH_REGEX = 'MATCH_REGEX';
}
