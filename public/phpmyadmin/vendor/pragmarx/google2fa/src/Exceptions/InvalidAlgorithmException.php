<?php

namespace PragmaRX\Google2FA\Exceptions;

use PragmaRX\Google2FA\Exceptions\Contracts\Google2FA as Google2FAExceptionContract;
use PragmaRX\Google2FA\Exceptions\Contracts\InvalidAlgorithm as InvalidAlgorithmExceptionContract;

class InvalidAlgorithmException extends Google2FAException implements Google2FAExceptionContract, InvalidAlgorithmExceptionContract
{
    protected $message = 'Invalid HMAC algorithm.';
}
