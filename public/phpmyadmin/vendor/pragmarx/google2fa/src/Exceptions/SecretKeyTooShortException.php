<?php

namespace PragmaRX\Google2FA\Exceptions;

use PragmaRX\Google2FA\Exceptions\Contracts\Google2FA as Google2FAExceptionContract;
use PragmaRX\Google2FA\Exceptions\Contracts\SecretKeyTooShort as SecretKeyTooShortExceptionContract;

class SecretKeyTooShortException extends Google2FAException implements Google2FAExceptionContract, SecretKeyTooShortExceptionContract
{
    protected $message = 'Secret key is too short. Must be at least 16 base32 characters';
}
