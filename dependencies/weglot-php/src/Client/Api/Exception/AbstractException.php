<?php

namespace Weglot\Client\Api\Exception;

abstract class AbstractException extends \Exception
{
    /**
     * @var int
     */
    protected $weglotCode;

    /**
     * @var array<mixed>
     */
    protected $jsonBody;

    /**
     * @param string       $message
     * @param int          $weglotCode
     * @param array<mixed> $jsonBody
     */
    public function __construct($message, $weglotCode = WeglotCode::GENERIC, $jsonBody = [])
    {
        parent::__construct($message);

        $this->weglotCode = $weglotCode;
        $this->jsonBody = $jsonBody;
    }

    /**
     * @return int
     */
    public function getWeglotCode()
    {
        return $this->weglotCode;
    }

    /**
     * @return array<mixed>
     */
    public function getJsonBody()
    {
        return $this->jsonBody;
    }
}
