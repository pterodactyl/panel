<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\RecoveryToken;

class RecoveryTokenRepository extends EloquentRepository
{
    public function model(): string
    {
        return RecoveryToken::class;
    }
}
