<?php

namespace CodeLts\U2F\U2FServer;

class Registration
{
    /** The key handle of the registered authenticator */
    protected $keyHandle;
/** The public key of the registered authenticator */
    protected $publicKey;
/** The attestation certificate of the registered authenticator */
    protected $certificate;
/** The counter associated with this registration */
    protected $counter = -1;

/**
     * @param string $keyHandle
     */
    public function setKeyHandle($keyHandle)
    {
        $this->keyHandle = $keyHandle;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @param string $certificate
     */
    public function setCertificate($certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * @return string
     */
    public function getKeyHandle()
    {
        return $this->keyHandle;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getCertificate()
    {
        return $this->certificate;
    }

    /**
     * @return string
     */
    public function getCounter()
    {
        return $this->counter;
    }

}
