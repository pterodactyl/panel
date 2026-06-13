<?php

namespace Pterodactyl\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property int $server_id
 * @property array $permissions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property User $user
 * @property Server $server
 */
class Subuser extends Model
{
    /** @use HasFactory<\Database\Factories\SubuserFactory> */
    use HasFactory;
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Pterodactyl\Models\Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the user associated with a subuser.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Pterodactyl\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the permissions associated with a subuser.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
