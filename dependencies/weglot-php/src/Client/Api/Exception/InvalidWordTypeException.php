<?php

namespace Weglot\Client\Api\Exception;

class InvalidWordTypeException extends AbstractException
{
    public function __construct()
    {
        parent::__construct(
            'The given WordType is invalid.',
            WeglotCode::PARAMETERS
        );
    }
}
