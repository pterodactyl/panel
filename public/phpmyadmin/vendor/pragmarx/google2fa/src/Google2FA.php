<?php

namespace PragmaRX\Google2FA;

use PragmaRX\Google2FA\Exceptions\InvalidAlgorithmException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Support\Base32;
use PragmaRX\Google2FA\Support\Constants;
use PragmaRX\Google2FA\Support\QRCode;

class Google2FA
{
    use QRCode;
    use Base32;

    /**
     * Algorithm.
     *
     * @var string
     */
    protected $algorithm = Constants::SHA1;

    /**
     * Length of the Token generated.
     *
     * @var int
     */
    protected $oneTimePasswordLength = 6;

    /**
     * Interval between key regeneration.
     *
     * @var int
     */
    protected $keyRegeneration = 30;

    /**
     * Secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Window.
     *
     * @var int
     */
    protected $window = 1; // Keys will be valid for 60 seconds

    /**
     * Find a valid One Time Password.
     *
     * @param string   $secret
     * @param string   $key
     * @param int|null $window
     * @param int      $startingTimestamp
     * @param int      $timestamp
     * @param int|null $oldTimestamp
     *
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     *
     * @return bool|int
     */
    public function findValidOTP(
        $secret,
        $key,
        $window,
        $startingTimestamp,
        $timestamp,
        $oldTimestamp = null
    ) {
        for (;
            $startingTimestamp <= $timestamp + $this->getWindow($window);
            $startingTimestamp++
        ) {
            if (
                hash_equals($this->oathTotp($secret, $startingTimestamp), $key)
            ) {
                return is_null($oldTimestamp)
                    ? true
                    : $startingTimestamp;
            }
        }

        return false;
    }

    /**
     * Generate the HMAC OTP.
     *
     * @param string $secret
     * @param int    $counter
     *
     * @return string
     */
    protected function generateHotp($secret, $counter)
    {
        return hash_hmac(
            $this->getAlgorithm(),
            pack('N*', 0, $counter), // Counter must be 64-bit int
            $secret,
            true
        );
    }

    /**
     * Generate a digit secret key in base32 format.
     *
     * @param int    $length
     * @param string $prefix
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     *
     * @return string
     */
    public function generateSecretKey($length = 16, $prefix = '')
    {
        return $this->generateBase32RandomKey($length, $prefix);
    }

    /**
     * Get the current one time password for a key.
     *
     * @param string $secret
     *
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     *
     * @return string
     */
    public function getCurrentOtp($secret)
    {
        return $this->oathTotp($secret, $this->getTimestamp());
    }

    /**
     * Get the HMAC algorithm.
     *
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * Get key regeneration.
     *
     * @return int
     */
    public function getKeyRegeneration()
    {
        return $this->keyRegeneration;
    }

    /**
     * Get OTP length.
     *
     * @return int
     */
    public function getOneTimePasswordLength()
    {
        return $this->oneTimePasswordLength;
    }

    /**
     * Get secret.
     *
     * @param string|null $secret
     *
     * @return string
     */
    public function getSecret($secret = null)
    {
        return is_null($secret) ? $this->secret : $secret;
    }

    /**
     * Returns the current Unix Timestamp divided by the $keyRegeneration
     * period.
     *
     * @return int
     **/
    public function getTimestamp()
    {
        return (int) floor(microtime(true) / $this->keyRegeneration);
    }

    /**
     * Get a list of valid HMAC algorithms.
     *
     * @return array
     */
    protected function getValidAlgorithms()
    {
        return [
            Constants::SHA1,
            Constants::SHA256,
            Constants::SHA512,
        ];
    }

    /**
     * Get the OTP window.
     *
     * @param null|int $window
     *
     * @return int
     */
    public function getWindow($window = null)
    {
        return is_null($window) ? $this->window : $window;
    }

    /**
     * Make a window based starting timestamp.
     *
     * @param int|null $window
     * @param int      $timestamp
     * @param int|null $oldTimestamp
     *
     * @return mixed
     */
    private function makeStartingTimestamp($window, $timestamp, $oldTimestamp = null)
    {
        return is_null($oldTimestamp)
            ? $timestamp - $this->getWindow($window)
            : max($timestamp - $this->getWindow($window), $oldTimestamp + 1);
    }

    /**
     * Get/use a starting timestamp for key verification.
     *
     * @param string|int|null $timestamp
     *
     * @return int
     */
    protected function makeTimestamp($timestamp = null)
    {
        if (is_null($timestamp)) {
            return $this->getTimestamp();
        }

        return (int) $timestamp;
    }

    /**
     * Takes the secret key and the timestamp and returns the one time
     * password.
     *
     * @param string $secret  Secret key in binary form.
     * @param int    $counter Timestamp as returned by getTimestamp.
     *
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws Exceptions\IncompatibleWithGoogleAuthenticatorException
     *
     * @return string
     */
    public function oathTotp($secret, $counter)
    {
        if (strlen($secret) < 8) {
            throw new SecretKeyTooShortException();
        }

        $secret = $this->base32Decode($this->getSecret($secret));

        return str_pad(
            $this->oathTruncate($this->generateHotp($secret, $counter)),
            $this->getOneTimePasswordLength(),
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Extracts the OTP from the SHA1 hash.
     *
     * @param string $hash
     *
     * @return string
     **/
    public function oathTruncate($hash)
    {
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;

        $temp = unpack('N', substr($hash, $offset, 4));

        $temp = $temp[1] & 0x7FFFFFFF;

        return substr(
            (string) $temp,
            -$this->getOneTimePasswordLength()
        );
    }

    /**
     * Remove invalid chars from a base 32 string.
     *
     * @param string $string
     *
     * @return string|null
     */
    public function removeInvalidChars($string)
    {
        return preg_replace(
            '/[^'.Constants::VALID_FOR_B32.']/',
            '',
            $string
        );
    }

    /**
     * Setter for the enforce Google Authenticator compatibility property.
     *
     * @param mixed $enforceGoogleAuthenticatorCompatibility
     *
     * @return $this
     */
    public function setEnforceGoogleAuthenticatorCompatibility(
        $enforceGoogleAuthenticatorCompatibility
    ) {
        $this->enforceGoogleAuthenticatorCompatibility = $enforceGoogleAuthenticatorCompatibility;

        return $this;
    }

    /**
     * Set the HMAC hashing algorithm.
     *
     * @param mixed $algorithm
     *
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidAlgorithmException
     *
     * @return \PragmaRX\Google2FA\Google2FA
     */
    public function setAlgorithm($algorithm)
    {
        // Default to SHA1 HMAC algorithm
        if (!in_array($algorithm, $this->getValidAlgorithms())) {
            throw new InvalidAlgorithmException();
        }

        $this->algorithm = $algorithm;

        return $this;
    }

    /**
     * Set key regeneration.
     *
     * @param mixed $keyRegeneration
     */
    public function setKeyRegeneration($keyRegeneration)
    {
        $this->keyRegeneration = $keyRegeneration;
    }

    /**
     * Set OTP length.
     *
     * @param mixed $oneTimePasswordLength
     */
    public function setOneTimePasswordLength($oneTimePasswordLength)
    {
        $this->oneTimePasswordLength = $oneTimePasswordLength;
    }

    /**
     * Set secret.
     *
     * @param mixed $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Set the OTP window.
     *
     * @param mixed $window
     */
    public function setWindow($window)
    {
        $this->window = $window;
    }

    /**
     * Verifies a user inputted key against the current timestamp. Checks $window
     * keys either side of the timestamp.
     *
     * @param string   $key          User specified key
     * @param string   $secret
     * @param null|int $window
     * @param null|int $timestamp
     * @param null|int $oldTimestamp
     *
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     *
     * @return bool|int
     */
    public function verify(
        $key,
        $secret,
        $window = null,
        $timestamp = null,
        $oldTimestamp = null
    ) {
        return $this->verifyKey(
            $secret,
            $key,
            $window,
            $timestamp,
            $oldTimestamp
        );
    }

    /**
     * Verifies a user inputted key against the current timestamp. Checks $window
     * keys either side of the timestamp.
     *
     * @param string   $secret
     * @param string   $key          User specified key
     * @param int|null $window
     * @param null|int $timestamp
     * @param null|int $oldTimestamp
     *
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     *
     * @return bool|int
     */
    public function verifyKey(
        $secret,
        $key,
        $window = null,
        $timestamp = null,
        $oldTimestamp = null
    ) {
        $timestamp = $this->makeTimestamp($timestamp);

        return $this->findValidOTP(
            $secret,
            $key,
            $window,
            $this->makeStartingTimestamp($window, $timestamp, $oldTimestamp),
            $timestamp,
            $oldTimestamp
        );
    }

    /**
     * Verifies a user inputted key against the current timestamp. Checks $window
     * keys either side of the timestamp, but ensures that the given key is newer than
     * the given oldTimestamp. Useful if you need to ensure that a single key cannot
     * be used twice.
     *
     * @param string   $secret
     * @param string   $key          User specified key
     * @param int|null $oldTimestamp The timestamp from the last verified key
     * @param int|null $window
     * @param int|null $timestamp
     *
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     *
     * @return bool|int
     */
    public function verifyKeyNewer(
        $secret,
        $key,
        $oldTimestamp,
        $window = null,
        $timestamp = null
    ) {
        return $this->verifyKey(
            $secret,
            $key,
            $window,
            $timestamp,
            $oldTimestamp
        );
    }
}
