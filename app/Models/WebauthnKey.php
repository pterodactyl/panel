<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebauthnKey extends \LaravelWebauthn\Models\WebauthnKey
{
    use HasFactory;

    public const RESOURCE_NAME = 'webauthn_key';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
