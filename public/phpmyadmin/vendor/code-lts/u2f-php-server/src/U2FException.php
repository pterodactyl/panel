<?php

namespace CodeLts\U2F\U2FServer;

class U2FException extends \Exception
{
    /**
     * Error for the authentication message not matching any outstanding
     * authentication request
     */
    public const NO_MATCHING_REQUEST = 1;
    /** Error for the authentication message not matching any registration */
    public const NO_MATCHING_REGISTRATION = 2;
    /**
     * Error for the signature on the authentication message not verifying with
     * the correct key
     */
    public const AUTHENTICATION_FAILURE = 3;
    /** Error for the challenge in the registration message not matching the
     * registration challenge */
    public const UNMATCHED_CHALLENGE = 4;
    /**
     * Error for the attestation signature on the registration message not
     * verifying
     */
    public const ATTESTATION_SIGNATURE = 5;
    /** Error for the attestation verification not verifying */
    public const ATTESTATION_VERIFICATION = 6;
    /** Error for not getting good random from the system */
    public const BAD_RANDOM = 7;
    /** Error when the counter is lower than expected */
    public const COUNTER_TOO_LOW = 8;
    /** Error decoding public key */
    public const PUBKEY_DECODE = 9;
    /** Error user-agent returned error */
    public const BAD_UA_RETURNING = 10;
    /** Error old OpenSSL version */
    public const OLD_OPENSSL = 11;

    /**
     * Override constructor and make message and code mandatory
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message, $code, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
