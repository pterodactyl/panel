<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\DatabaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Facades\Hashids;

/**
 * @property-read DatabaseHost $host
 */
#[Fillable([
    'server_id', 'database_host_id', 'database', 'username', 'password', 'remote', 'max_connections',
])]
#[Hidden(['password'])]
class Database extends Model
{
    /** @use HasFactory<DatabaseFactory> */
    use HasFactory;

    public const string RESOURCE_NAME = 'server_database';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'server_id' => 'integer',
            'database_host_id' => 'integer',
            'max_connections' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Resolves the database using the ID by checking if the value provided is a HashID
     * string value, or just the ID to the database itself.
     *
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        if (($field ?? $this->getRouteKeyName()) === 'id' && (is_string($value) || is_int($value))) {
            $value = is_int($value) || ctype_digit($value)
                ? $value
                : Hashids::decodeFirst($value);
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    /**
     * @return BelongsTo<DatabaseHost, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(DatabaseHost::class, 'database_host_id');
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
