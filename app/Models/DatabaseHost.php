<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\DatabaseHostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'host', 'port', 'username', 'password', 'max_databases', 'node_id',
])]
#[Hidden(['password'])]
class DatabaseHost extends Model
{
    /** @use HasFactory<DatabaseHostFactory> */
    use HasFactory;

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
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * @return HasMany<Database, $this>
     */
    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }
}
