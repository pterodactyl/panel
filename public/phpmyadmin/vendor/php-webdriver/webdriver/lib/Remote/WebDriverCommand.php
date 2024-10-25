<?php

namespace Facebook\WebDriver\Remote;

class WebDriverCommand
{
    /** @var string|null */
    protected $sessionID;
    /** @var string */
    protected $name;
    /** @var array */
    protected $parameters;

    /**
     * @param string $session_id
     * @param string $name Constant from DriverCommand
     * @param array $parameters
     * @todo In 2.0 force parameters to be an array, then remove is_array() checks in HttpCommandExecutor
     * @todo In 2.0 make constructor private. Use by default static `::create()` with sessionID type string.
     */
    public function __construct($session_id, $name, $parameters)
    {
        $this->sessionID = $session_id;
        $this->name = $name;
        $this->parameters = $parameters;
    }

    /**
     * @return self
     */
    public static function newSession(array $parameters)
    {
        // TODO: In 2.0 call empty constructor and assign properties directly.
        return new self(null, DriverCommand::NEW_SESSION, $parameters);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null Could be null for newSession command
     */
    public function getSessionID()
    {
        return $this->sessionID;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
