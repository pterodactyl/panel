<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\AllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Models\Builders\AllocationBuilder;
use Pterodactyl\Models\Traits\HasHashid;

/**
 * @property-read Node $node
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
#[UseEloquentBuilder(AllocationBuilder::class)]
class Allocation extends Model
{
    /** @use HasFactory<AllocationFactory> */
    use HasFactory;

    use HasHashid;

    public const string RESOURCE_NAME = 'allocation';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'port' => 'integer',
            'server_id' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    public function toString(): string
    {
        return sprintf('%s:%s', $this->ip, $this->port);
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function alias(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->ip_alias ?? $this->ip);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasAlias(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->ip_alias !== null);
    }
}
