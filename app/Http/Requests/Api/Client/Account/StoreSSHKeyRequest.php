<?php

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\RSA;
use Pterodactyl\Models\UserSSHKey;
use Illuminate\Validation\Validator;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Exception\NoKeyLoadedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreSSHKeyRequest extends ClientApiRequest
{
    private const SK_PUBLIC_KEY_TYPES = [
        'sk-ssh-ed25519@openssh.com' => true,
        'sk-ecdsa-sha2-nistp256@openssh.com' => true,
    ];

    protected ?PublicKey $key = null;

    protected ?string $publicKey = null;

    protected ?string $fingerprint = null;

    /**
     * Returns the rules for this request.
     */
    public function rules(): array
    {
        return [
            'name' => UserSSHKey::getRulesForField('name'),
            'public_key' => UserSSHKey::getRulesForField('public_key'),
        ];
    }

    /**
     * Check to see if this SSH key has already been added to the user's account
     * and if so return an error.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function () {
            try {
                $publicKey = trim((string) $this->input('public_key'));

                if ($this->isSecurityKey($publicKey)) {
                    $this->loadSecurityKey($publicKey);
                } else {
                    $this->key = PublicKeyLoader::loadPublicKey($publicKey);
                }

                if ($this->key instanceof DSA) {
                    $this->validator->errors()->add('public_key', 'DSA keys are not supported.');
                }

                if ($this->key instanceof RSA && $this->key->getLength() < 2048) {
                    $this->validator->errors()->add('public_key', 'RSA keys must be at least 2048 bytes in length.');
                }

                if ($this->publicKey === null || $this->fingerprint === null) {
                    if ($this->key !== null) {
                        $this->publicKey = $this->key->toString('PKCS8');
                        $this->fingerprint = $this->key->getFingerprint('sha256');
                    }
                }
            } catch (NoKeyLoadedException $exception) {
                $this->validator->errors()->add('public_key', 'The public key provided is not valid.');

                return;
            }

            $fingerprint = $this->fingerprint;
            if ($this->user()->sshKeys()->where('fingerprint', $fingerprint)->exists()) {
                $this->validator->errors()->add('public_key', 'The public key provided already exists on your account.');
            }
        });
    }

    /**
     * Returns the public key but formatted in a consistent manner.
     */
    public function getPublicKey(): string
    {
        if ($this->publicKey === null) {
            throw new \Exception('The public key was not properly loaded for this request.');
        }

        return $this->publicKey;
    }

    /**
     * Returns the SHA256 fingerprint of the key provided.
     */
    public function getKeyFingerprint(): string
    {
        if ($this->fingerprint === null) {
            throw new \Exception('The public key was not properly loaded for this request.');
        }

        return $this->fingerprint;
    }

    private function isSecurityKey(string $publicKey): bool
    {
        $type = strtok($publicKey, " \t\n\r\0\x0B");

        return is_string($type) && isset(self::SK_PUBLIC_KEY_TYPES[$type]);
    }

    private function loadSecurityKey(string $publicKey): void
    {
        $parts = preg_split('/\s+/', $publicKey, 3);

        if ($parts === false || count($parts) < 2 || !isset(self::SK_PUBLIC_KEY_TYPES[$parts[0]])) {
            throw new NoKeyLoadedException('Unable to read key');
        }

        $decoded = base64_decode($parts[1], true);
        if ($decoded === false) {
            throw new NoKeyLoadedException('Unable to read key');
        }

        try {
            [$blobType] = Strings::unpackSSH2('s', $decoded);
        } catch (\Throwable $exception) {
            throw new NoKeyLoadedException('Unable to read key');
        }

        if ($blobType !== $parts[0]) {
            throw new NoKeyLoadedException('Unable to read key');
        }

        $this->key = null;
        $this->publicKey = $publicKey;
        $this->fingerprint = rtrim(base64_encode(hash('sha256', $decoded, true)), '=');
    }
}
