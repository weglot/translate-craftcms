<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Regex;

class RegexChecker
{
    /**
     * @var string
     */
    public $regex = '';
    /**
     * @var string
     */
    public $type = '';
    /**
     * @var int
     */
    public $var_number = 1;
    /**
     * @var array<int, string>
     */
    public $keys = [];
    /**
     * @var callable|null
     */
    public $callback;
    /**
     * @var callable|null
     */
    public $revert_callback;
    /**
     * @param string             $regex
     * @param string             $type
     * @param int                $var_number
     * @param array<int, string> $keys
     * @param callable|null      $callback
     * @param callable|null      $revert_callback
     */
    public function __construct($regex = '', $type = '', $var_number = 0, $keys = [], $callback = null, $revert_callback = null)
    {
        $this->regex = $regex;
        $this->type = $type;
        $this->var_number = $var_number;
        $this->keys = $keys;
        $this->callback = $callback;
        $this->revert_callback = $revert_callback;
    }
    /**
     * @return array{string, string, int, array<int, string>, callable|null, callable|null}
     */
    public function toArray()
    {
        return [$this->regex, $this->type, $this->var_number, $this->keys, $this->callback, $this->revert_callback];
    }
}
