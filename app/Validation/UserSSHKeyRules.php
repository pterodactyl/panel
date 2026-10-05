<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class UserSSHKeyRules
{
    /**
     * The longest public key, in bytes, that is accepted or parsed.
     */
    public const int PUBLIC_KEY_MAX_LENGTH = 16384;

    /**
     * The prefixes of the public key formats users may add.
     *
     * @var list<string>
     */
    private const array PUBLIC_KEY_PREFIXES = [
        'ssh-rsa ',
        'ssh-ed25519 ',
        'ecdsa-sha2-',
        'sk-ssh-ed25519@openssh.com ',
        'sk-ecdsa-sha2-nistp256@openssh.com ',
        '-----BEGIN PUBLIC KEY-----',
        '-----BEGIN RSA PUBLIC KEY-----',
        '-----BEGIN EC PUBLIC KEY-----',
        '-----BEGIN DSA PUBLIC KEY-----',
        '---- BEGIN SSH2 PUBLIC KEY ----',
    ];

    /**
     * Validation rules for the client SSH key request.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
            'public_key' => ['required', 'string', 'max:'.self::PUBLIC_KEY_MAX_LENGTH],
        ];
    }

    /**
     * Whether a value is a supported public key format that is safe to parse: not empty,
     * no longer than the limit, without NUL bytes, and starting with a known key prefix.
     * Certificates are not public keys and are rejected.
     */
    public static function isSupportedPublicKey(string $value): bool
    {
        $value = mb_trim($value);

        if ($value === '' || mb_strlen($value) > self::PUBLIC_KEY_MAX_LENGTH || str_contains($value, "\0")) {
            return false;
        }

        foreach (self::PUBLIC_KEY_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
