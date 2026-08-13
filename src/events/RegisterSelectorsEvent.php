<?php

declare(strict_types=1);

namespace weglot\craftweglot\events;

use yii\base\Event;

final class RegisterSelectorsEvent extends Event
{
    /**
     * @param array<int, array{value: string}> $selectors
     * @param array<string, mixed>             $config
     */
    public function __construct(public array $selectors, array $config = [])
    {
        parent::__construct($config);
    }
}
