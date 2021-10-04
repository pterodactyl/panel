<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Pterodactyl\Models\User $user
 */
class RecoveryToken extends Model
{
    /**
     * There are no updates to this model, only inserts and deletes.
     */
    public const UPDATED_AT = null;

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var bool
     */
    protected $immutableDates = true;

    /**
     * @var string[]
     */
    public static $validationRules = [
        'token' => 'required|string',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
