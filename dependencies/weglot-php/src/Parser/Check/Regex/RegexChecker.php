<?php

namespace Weglot\Parser\Check\Regex;

class RegexChecker
{
    /**
     * DOM node to match.
     *
     * @var string
     */
    public $regex = '';

    /**
     * DOM node to match.
     *
     * @var string
     */
    public $type = '';

    /**
     * @var int
     */
    public $var_number = 1;

    /**
     * DOM node to match.
     *
     * @var array
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
     * @param string        $regex
     * @param string        $type
     * @param int           $var_number
     * @param array         $keys
     * @param callable|null $callback
     * @param callable|null $revert_callback
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
     * @return array
     */
    public function toArray()
    {
        return [
            $this->regex,
            $this->type,
            $this->var_number,
            $this->keys,
            $this->callback,
            $this->revert_callback,
        ];
    }
}
