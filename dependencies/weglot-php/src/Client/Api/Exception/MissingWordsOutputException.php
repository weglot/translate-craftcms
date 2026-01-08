<?php

namespace Weglot\Client\Api\Exception;

class MissingWordsOutputException extends \Exception
{
    public function __construct()
    {
        parent::__construct(
            'There is no output words.',
            WeglotCode::GENERIC
        );
    }
}
