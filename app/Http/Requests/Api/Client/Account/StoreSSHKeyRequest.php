<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Account;

use Closure;
use Illuminate\Validation\Validator;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Exception\NoKeyLoadedException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\UserSSHKeyRules;
use UnexpectedValueException;

class StoreSSHKeyRequest extends ClientApiRequest
{
    protected ?PublicKey $key = null;

    /**
     * Returns the rules for this request.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'name' => UserSSHKeyRules::rules()['name'],
            'public_key' => UserSSHKeyRules::rules()['public_key'],
        ];
    }

    /**
     * Check to see if this SSH key has already been added to the user's account
     * and if so return an error.
     */
    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('public_key')) {
                return;
            }

            $publicKey = mb_trim(JsonValueGuard::string($this->input('public_key')));
            if (! UserSSHKeyRules::isSupportedPublicKey($publicKey)) {
                $validator->errors()->add('public_key', 'The public key provided is not valid.');

                return;
            }

            try {
                $this->key = PublicKeyLoader::loadPublicKey($publicKey);
            } catch (NoKeyLoadedException) {
                $validator->errors()->add('public_key', 'The public key provided is not valid.');

                return;
            }

            if ($this->key instanceof DSA) {
                $validator->errors()->add('public_key', 'DSA keys are not supported.');
            }

            if ($this->key instanceof RSA && $this->key->getLength() < 2048) {
                $validator->errors()->add('public_key', 'RSA keys must be at least 2048 bytes in length.');
            }

            $fingerprint = $this->key->getFingerprint('sha256');
            if ($this->user()->sshKeys()->where('fingerprint', $fingerprint)->exists()) {
                $validator->errors()->add('public_key', 'The public key provided already exists on your account.');
            }
        }];
    }

    /**
     * Returns the public key but formatted in a consistent manner.
     */
    public function getPublicKey(): string
    {
        $publicKey = $this->loadedKey()->toString('PKCS8');
        throw_unless(is_string($publicKey), UnexpectedValueException::class, 'The loaded public key could not be serialized.');

        return $publicKey;
    }

    /**
     * Returns the SHA256 fingerprint of the key provided.
     */
    public function getKeyFingerprint(): string
    {
        $fingerprint = $this->loadedKey()->getFingerprint('sha256');
        throw_unless(is_string($fingerprint), UnexpectedValueException::class, 'The loaded public key did not produce a SHA256 fingerprint.');

        return $fingerprint;
    }

    private function loadedKey(): PublicKey
    {
        return $this->key ?? throw new UnexpectedValueException('The public key was not properly loaded for this request.');
    }
}
