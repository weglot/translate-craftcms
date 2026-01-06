<?php

namespace Weglot\Client\Api\Exception;

class InvalidLanguageException extends AbstractException
{
    public function __construct()
    {
        parent::__construct(
            'The given language is invalid.',
            WeglotCode::PARAMETERS
        );
    }
}
