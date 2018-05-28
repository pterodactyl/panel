<?php

namespace Pterodactyl\Traits\Helpers;

use Lcobucci\JWT\Signer;
use Illuminate\Support\Str;

trait ProvidesJWTServices
{
    /**
     * Get the signing key to use when creating JWTs.
     *
     * @return string
     */
    public function getJWTSigningKey(): string
    {
        $key = config()->get('jwt.key', '');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
    }

    /**
     * Provide the signing algo to use for JWT.
     *
     * @return \Lcobucci\JWT\Signer
     */
    public function getJWTSigner(): Signer
    {
        $class = config()->get('jwt.signer');

        return new $class;
    }
}
