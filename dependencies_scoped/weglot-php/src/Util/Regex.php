<?php

namespace Weglot\Vendor\Weglot\Util;

use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;
class Regex
{
    /**
     * Preferred delimiter to use for PHP regex in order to avoid conflict with url values:
     * - We have a lot of real URL in database, so `/` should be avoided as delimiter.
     * - `#` is used as anchor in URL so cannot be used as delimiter.
     */
    const REGEX_DELIMITER = '~';
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $value;
    /**
     * @param string $type
     * @param string $value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
    /**
     * @return string
     */
    public function getRegex()
    {
        $value = RegexEnum::MATCH_REGEX === $this->type ? $this->value : preg_quote($this->value, self::REGEX_DELIMITER);
        switch ($this->type) {
            case RegexEnum::START_WITH:
                return \sprintf('^%s', $value);
            case RegexEnum::NOT_START_WITH:
                return \sprintf('^(?!%s)', $value);
            case RegexEnum::END_WITH:
                return \sprintf('%s$', $value);
            case RegexEnum::NOT_END_WITH:
                return \sprintf('(?<!%s)$', $value);
            case RegexEnum::CONTAIN:
                return \sprintf('%s', $value);
            case RegexEnum::NOT_CONTAIN:
                return \sprintf('^((?!%s).)*$', $value);
            case RegexEnum::IS_EXACTLY:
                return \sprintf('^%s$', $value);
            case RegexEnum::NOT_IS_EXACTLY:
                return \sprintf('^(?!%s$)', $value);
            case RegexEnum::MATCH_REGEX:
                return $value;
        }
        return $value;
    }
    /**
     * @param string $value
     *
     * @return bool
     */
    public function match($value)
    {
        switch ($this->type) {
            case RegexEnum::START_WITH:
                return str_starts_with($value, $this->value);
            case RegexEnum::NOT_START_WITH:
                return !str_starts_with($value, $this->value);
            case RegexEnum::END_WITH:
                return str_ends_with($value, $this->value);
            case RegexEnum::NOT_END_WITH:
                return !str_ends_with($value, $this->value);
            case RegexEnum::CONTAIN:
                return str_contains($value, $this->value);
            case RegexEnum::NOT_CONTAIN:
                return !str_contains($value, $this->value);
            case RegexEnum::IS_EXACTLY:
                return $value === $this->value;
            case RegexEnum::NOT_IS_EXACTLY:
                return $value !== $this->value;
            default:
                return 1 === preg_match($this->getPHPRegex(), $value);
        }
    }
    /**
     * @return string
     */
    private function getPHPRegex()
    {
        return \sprintf('%s%s%s', self::REGEX_DELIMITER, $this->getRegex(), self::REGEX_DELIMITER);
    }
}
