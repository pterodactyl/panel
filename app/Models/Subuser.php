<?php

namespace Pterodactyl\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $server_id
 * @property array $permissions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\User $user
 * @property \Pterodactyl\Models\Server $server
 */
class Subuser extends Model
{
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_subuser';

    /**
     * The table associated with the model.
     */
    protected $table = 'subusers';

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'user_id' => 'int',
        'server_id' => 'int',
        'permissions' => 'array',
    ];

    public static array $validationRules = [
        'user_id' => 'required|numeric|exists:users,id',
        'server_id' => 'required|numeric|exists:servers,id',
        'permissions' => 'nullable|array',
        'permissions.*' => 'string',
    ];

    /**
     * Return a hashid encoded string to represent the ID of the subuser.
     */
    public function getHashidAttribute(): string
    {
        return app()->make('hashids')->encode($this->id);
    }

    /**
     * Gets the server associated with a subuser.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the user associated with a subuser.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the permissions associated with a subuser.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
