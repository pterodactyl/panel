<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\AllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Models\Traits\HasHashid;

/**
 * @property-read Node $node
 */
#[Guarded(['id', 'created_at', 'updated_at'])]
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

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function unassigned(Builder $query): void
    {
        $query->whereNull('server_id');
    }

    /**
     * Allocations on any of the given nodes; an empty list leaves the query unrestricted.
     *
     * @param  Builder<self>  $query
     * @param  list<int>  $nodes
     */
    #[Scope]
    protected function onNodes(Builder $query, array $nodes): void
    {
        $query->when($nodes !== [], fn (Builder $q) => $q->whereIn('node_id', $nodes));
    }

    /**
     * Allocations whose port is one of the given ports or falls inside one of the
     * given [start, end] ranges; with neither given the query is unrestricted.
     *
     * @param  Builder<self>  $query
     * @param  list<int>  $ports
     * @param  list<array{int, int}>  $ranges
     */
    #[Scope]
    protected function onPorts(Builder $query, array $ports, array $ranges = []): void
    {
        if ($ports === [] && $ranges === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($ports, $ranges): void {
            if ($ports !== []) {
                $inner->orWhereIn('port', $ports);
            }

            foreach ($ranges as $range) {
                $inner->orWhereBetween('port', $range);
            }
        });
    }

    /**
     * Allocations on node/IP pairs that no server uses yet.
     *
     * @param  Builder<self>  $query
     * @param  list<int>  $nodes  restrict the occupied-IP lookup to these nodes
     */
    #[Scope]
    protected function onDedicatedIp(Builder $query, array $nodes = []): void
    {
        $occupied = self::query()
            ->selectRaw('CONCAT_WS("-", node_id, ip)')
            ->whereNotNull('server_id')
            ->onNodes($nodes);

        $query->whereNotIn($query->raw('CONCAT_WS("-", node_id, ip)'), $occupied);
    }
}
