<?php

namespace weglot\craftweglot\events;

use yii\base\Event;

final class RegisterSelectorsEvent extends Event
{
    /**
     * @param array<int, array{value: string}> $selectors
     */
    public function __construct(public array $selectors, $config = [])
    {
        parent::__construct($config);
    }
}
