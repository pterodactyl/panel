<?php

namespace Facebook\WebDriver\Remote;

/**
 * Represents status of remote end
 *
 * @see https://www.w3.org/TR/webdriver/#status
 */
class RemoteStatus
{
    /** @var bool */
    protected $isReady;
    /** @var string */
    protected $message;
    /** @var array */
    protected $meta = [];

    /**
     * @param bool $isReady
     * @param string $message
     */
    protected function __construct($isReady, $message, array $meta = [])
    {
        $this->isReady = (bool) $isReady;
        $this->message = (string) $message;

        $this->setMeta($meta);
    }

    /**
     * @param array $responseBody
     * @return RemoteStatus
     */
    public static function createFromResponse(array $responseBody)
    {
        $object = new static($responseBody['ready'], $responseBody['message'], $responseBody);

        return $object;
    }

    /**
     * The remote end's readiness state.
     * False if an attempt to create a session at the current time would fail.
     * However, the value true does not guarantee that a New Session command will succeed.
     *
     * @return bool
     */
    public function isReady()
    {
        return $this->isReady;
    }

    /**
     * An implementation-defined string explaining the remote end's readiness state.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Arbitrary meta information specific to remote-end implementation.
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    protected function setMeta(array $meta)
    {
        unset($meta['ready'], $meta['message']);

        $this->meta = $meta;
    }
}
