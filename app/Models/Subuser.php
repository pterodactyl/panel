<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\SubuserFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Pterodactyl\Models\Traits\HasHashid;

/**
 * @property list<string> $permissions
 * @property-read User $user
 * @property-read Server $server
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class Subuser extends Model
{
    /** @use HasFactory<SubuserFactory> */
    use HasFactory;

    use HasHashid;
    use Notifiable;

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
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
