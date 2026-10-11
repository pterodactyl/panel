<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Users\CreatesSshKeys;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;

final readonly class CreateSshKey implements CreatesSshKeys
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
     * An SSH key's fields: its name, the public key and the key's fingerprint. A key a
     * request parses is first held to PUBLIC_KEY_MAX_LENGTH and isSupportedPublicKey().
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'public_key' => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
        ];
    }

    /**
     * Validates SSH key input and returns the key it describes.
     *
     * @param  ActionInput  $input
     * @return array{name: string, public_key: string, fingerprint: string}
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        return [
            'name' => $input->string('name')->toString(),
            'public_key' => $input->string('public_key')->toString(),
            'fingerprint' => $input->string('fingerprint')->toString(),
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

    /** @throws ValidationException */
    public function create(User $user, string $name, string $publicKey, string $fingerprint): UserSSHKey
    {
        return $user->sshKeys()->create(self::attributes(['name' => $name, 'public_key' => $publicKey, 'fingerprint' => $fingerprint]));
    }
}
