<?php

namespace PragmaRX\Google2FA\Support;

class Constants
{
    /**
     * Characters valid for Base 32.
     */
    const VALID_FOR_B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Characters valid for Base 32, scrambled.
     */
    const VALID_FOR_B32_SCRAMBLED = '234567QWERTYUIOPASDFGHJKLZXCVBNM';

    /**
     * SHA1 algorithm.
     */
    const SHA1 = 'sha1';

    /**
     * SHA256 algorithm.
     */
    const SHA256 = 'sha256';

    /**
     * SHA512 algorithm.
     */
    const SHA512 = 'sha512';
}
