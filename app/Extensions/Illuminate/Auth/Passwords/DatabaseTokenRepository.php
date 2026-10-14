<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Illuminate\Auth\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository as IlluminateDatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SensitiveParameter;

class DatabaseTokenRepository extends IlluminateDatabaseTokenRepository
{
    /**
     * {@inheritdoc}
     */
    public function exists(CanResetPasswordContract $user, #[SensitiveParameter] $token): bool
    {
        $record = $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->first(['token', 'created_at']);
        $record = $record === null ? null : get_object_vars($record);

        if ($record === null || $this->tokenExpired(Arr::string($record, 'created_at'))) {
            $this->hasher->make(Str::random(64));

            return false;
        }

        return $this->hasher->check($token, Arr::string($record, 'token'));
    }
}
