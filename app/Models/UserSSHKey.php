<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $public_key
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Pterodactyl\Models\User $user
 */
class UserSSHKey extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'user_ssh_key';

    const UPDATED_AT = null;

    protected $table = 'user_ssh_keys';
    protected bool $immutableDates = true;

    /**
     * @var string[]
     */
    protected $guarded = ['id', 'created_at'];

    public static array $validationRules = [
        'name' => 'required|string',
        'public_key' => 'required|string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
