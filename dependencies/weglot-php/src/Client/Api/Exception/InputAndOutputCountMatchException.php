<?php

namespace Weglot\Client\Api\Exception;

class InputAndOutputCountMatchException extends AbstractException
{
    public function __construct(array $jsonBody)
    {
        parent::__construct(
            'Input and ouput words count doesn\'t match.',
            WeglotCode::PARAMETERS,
            $jsonBody
        );
    }
}
