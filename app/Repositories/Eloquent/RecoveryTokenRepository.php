<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\RecoveryToken;

class RecoveryTokenRepository extends EloquentRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return RecoveryToken::class;
    }
}
