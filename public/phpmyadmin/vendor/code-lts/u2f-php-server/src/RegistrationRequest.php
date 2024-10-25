<?php

namespace CodeLts\U2F\U2FServer;

class RegistrationRequest implements \JsonSerializable
{
    /** @var string Protocol version */
    protected $version = U2FServer::VERSION;
    /** @var string Registration challenge */
    protected $challenge;
    /** @var string Application id */
    protected $appId;

    /**
     * @param string $challenge
     * @param string $appId
     */
    public function __construct($challenge, $appId)
    {
        $this->challenge = $challenge;
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function challenge()
    {
        return $this->challenge;
    }

    /**
     * @return string
     */
    public function appId()
    {
        return $this->appId;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'version' => $this->version,
            'challenge' => $this->challenge,
            'appId' => $this->appId,
        ];
    }

}
