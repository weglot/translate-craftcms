<?php

declare(strict_types=1);

namespace Weglot\Client\Api\Exception;

abstract class WeglotCode
{
    public const GENERIC = 0;
    public const AUTH = 1;
    public const PARAMETERS = 2;
}
