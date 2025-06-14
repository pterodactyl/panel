<?php

namespace Pterodactyl\Classes\PayPal\sdk\Security;

/**
 * Class Cipher
 *
 * Helper class to encrypt/decrypt data with secret key
 *
 * @package PayPal\Security
 */
class Cipher
{
    private $secretKey;

    /**
     * Fixed IV Size
     */
    const IV_SIZE = 16;

    function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Encrypts the input text using the cipher key
     *
     * @param $input
     * @return string
     */
    function encrypt($input)
    {
        // Create a random IV. Not using mcrypt to generate one, as to not have a dependency on it.
        $iv = substr(uniqid("", true), 0, Cipher::IV_SIZE);
        // Encrypt the data
        $encrypted = openssl_encrypt($input, "AES-256-CBC", $this->secretKey, 0, $iv);
        // Encode the data with IV as prefix
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypts the input text from the cipher key
     *
     * @param $input
     * @return string
     */
    function decrypt($input)
    {
        // Decode the IV + data
        $input = base64_decode($input);
        // Remove the IV
        $iv = substr($input, 0, Cipher::IV_SIZE);
        // Return Decrypted Data
        return openssl_decrypt(substr($input, Cipher::IV_SIZE), "AES-256-CBC", $this->secretKey, 0, $iv);
    }
}
