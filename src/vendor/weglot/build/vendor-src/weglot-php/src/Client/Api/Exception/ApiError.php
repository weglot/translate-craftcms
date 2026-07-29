<?php

namespace Weglot\Vendor\Weglot\Client\Api\Exception;

use Weglot\Vendor\Weglot\Parser\Definitions\Exception\AbstractException;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\WeglotCode;
class ApiError extends AbstractException
{
    public function __construct($message, array $jsonBody = [])
    {
        parent::__construct($message, WeglotCode::AUTH, $jsonBody);
    }
}
