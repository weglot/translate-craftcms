<?php

namespace Weglot\Vendor\Weglot\Client\Api\Exception;

use Weglot\Vendor\Weglot\Parser\Definitions\Exception\WeglotCode;
class MissingWordsOutputException extends \Exception
{
    public function __construct()
    {
        parent::__construct('There is no output words.', WeglotCode::GENERIC);
    }
}
