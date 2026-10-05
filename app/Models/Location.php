<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Carbon\Carbon;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $short
 * @property string $long
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Node[] $nodes
 * @property Server[] $servers
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'location';

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Gets the nodes in a specified location.
     *
     * @return HasMany<Node, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Gets the servers within a given location.
     *
     * @return HasManyThrough<Server, Node, $this>
     */
    public function servers(): HasManyThrough
    {
        return $this->hasManyThrough(Server::class, Node::class);
    }
}
