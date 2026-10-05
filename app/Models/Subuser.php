<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\Carbon;
use Database\Factories\SubuserFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Pterodactyl\Models\Traits\HasHashid;

/**
 * @property int $id
 * @property int $user_id
 * @property int $server_id
 * @property list<string> $permissions
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User $user
 * @property Server $server
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class Subuser extends Model
{
    /** @use HasFactory<SubuserFactory> */
    use HasFactory;

    use HasHashid;
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'server_subuser';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'server_id' => 'int',
            'permissions' => 'array',
        ];
    }

    /**
     * Gets the server associated with a subuser.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the user associated with a subuser.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gets the permissions associated with a subuser.
     *
     * @return HasMany<Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
