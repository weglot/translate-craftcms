<?php

namespace Weglot\Vendor\Weglot\Client\Api\Exception;

use Weglot\Vendor\Weglot\Parser\Definitions\Exception\AbstractException;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\WeglotCode;
class InputAndOutputCountMatchException extends AbstractException
{
    public function __construct(array $jsonBody)
    {
        parent::__construct('Input and ouput words count doesn\'t match.', WeglotCode::PARAMETERS, $jsonBody);
    }
}
