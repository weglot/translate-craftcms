<?php

declare(strict_types=1);

namespace Weglot\Client\Api\Exception;

class ApiError extends AbstractException
{
    public function __construct($message, array $jsonBody = [])
    {
        parent::__construct($message, WeglotCode::AUTH, $jsonBody);
    }
}
