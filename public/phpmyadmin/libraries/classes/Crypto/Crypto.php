<?php

declare(strict_types=1);

namespace PhpMyAdmin\Crypto;

use Throwable;

use function is_string;
use function mb_strlen;
use function mb_substr;
use function random_bytes;
use function sodium_crypto_secretbox;
use function sodium_crypto_secretbox_open;

use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
use const SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

final class Crypto
{
    private function getEncryptionKey(): string
    {
        global $config;

        $key = $config->get('URLQueryEncryptionSecretKey');
        if (is_string($key) && mb_strlen($key, '8bit') === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }

        $key = $_SESSION['URLQueryEncryptionSecretKey'] ?? null;
        if (is_string($key) && mb_strlen($key, '8bit') === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }

        $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $_SESSION['URLQueryEncryptionSecretKey'] = $key;

        return $key;
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->getEncryptionKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return $nonce . $ciphertext;
    }

    public function decrypt(string $encrypted): ?string
    {
        $key = $this->getEncryptionKey();
        $nonce = mb_substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        try {
            $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        } catch (Throwable $e) {
            return null;
        }

        if (! is_string($decrypted)) {
            return null;
        }

        return $decrypted;
    }
}
