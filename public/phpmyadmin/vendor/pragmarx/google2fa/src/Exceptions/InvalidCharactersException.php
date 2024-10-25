<?php

namespace PragmaRX\Google2FA\Exceptions;

use PragmaRX\Google2FA\Exceptions\Contracts\Google2FA as Google2FAExceptionContract;
use PragmaRX\Google2FA\Exceptions\Contracts\InvalidCharacters as InvalidCharactersExceptionContract;

class InvalidCharactersException extends Google2FAException implements Google2FAExceptionContract, InvalidCharactersExceptionContract
{
    protected $message = 'Invalid characters in the base32 string.';
}
