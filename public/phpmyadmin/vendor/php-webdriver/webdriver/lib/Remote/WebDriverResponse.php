<?php

namespace Facebook\WebDriver\Remote;

class WebDriverResponse
{
    /**
     * @var int
     */
    private $status;
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var string
     */
    private $sessionID;

    /**
     * @param null|string $session_id
     */
    public function __construct($session_id = null)
    {
        $this->sessionID = $session_id;
    }

    /**
     * @return null|int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return WebDriverResponse
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return WebDriverResponse
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSessionID()
    {
        return $this->sessionID;
    }

    /**
     * @param mixed $session_id
     * @return WebDriverResponse
     */
    public function setSessionID($session_id)
    {
        $this->sessionID = $session_id;

        return $this;
    }
}
