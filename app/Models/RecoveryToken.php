<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property CarbonImmutable $created_at
 * @property User $user
 */
class RecoveryToken extends Model
{
    /**
     * There are no updates to this model, only inserts and deletes.
     */
    public const UPDATED_AT = null;

    public $timestamps = true;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
