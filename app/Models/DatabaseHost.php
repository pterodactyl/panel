<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DatabaseHostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $host
 * @property int $port
 * @property string $username
 * @property string $password
 * @property int|null $max_databases
 * @property int|null $node_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable([
    'name', 'host', 'port', 'username', 'password', 'max_databases', 'node_id',
])]
#[Hidden(['password'])]
class DatabaseHost extends Model
{
    /** @use HasFactory<DatabaseHostFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'database_host';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'max_databases' => 'integer',
            'node_id' => 'integer',
        ];
    }

    /**
     * Gets the node associated with a database host.
     *
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets the databases associated with this host.
     *
     * @return HasMany<Database, $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }
}
