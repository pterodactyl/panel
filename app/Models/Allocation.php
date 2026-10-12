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
use Pterodactyl\Actions\Allocations\CreateAllocations;
use Pterodactyl\Exceptions\DisplayException;
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
     * @param  array<int>  $nodes
     */
    #[Scope]
    protected function onNodes(Builder $query, array $nodes): void
    {
        $query->when($nodes !== [], fn (Builder $q) => $q->whereIn('node_id', $nodes));
    }

    /**
     * Allocations on any of the given ports or "start-end" port ranges. Entries that are
     * neither are ignored, and with none left the query is unrestricted.
     *
     * @param  Builder<self>  $query
     * @param  list<int|string>  $ports
     *
     * @throws DisplayException
     */
    #[Scope]
    protected function onPorts(Builder $query, array $ports): void
    {
        $single = [];
        $ranges = [];
        foreach ($ports as $port) {
            if (ctype_digit((string) $port)) {
                $single[] = (int) $port;
            }

            if (preg_match(CreateAllocations::PORT_RANGE_REGEX, (string) $port, $matches)) {
                throw_if(abs($matches[2] - $matches[1]) > CreateAllocations::PORT_RANGE_LIMIT, DisplayException::class, trans('exceptions.allocations.too_many_ports'));

                $ranges[] = [(int) $matches[1], (int) $matches[2]];
            }
        }

        if ($single === [] && $ranges === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($single, $ranges): void {
            if ($single !== []) {
                $inner->orWhereIn('port', $single);
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
     * @param  array<int>  $nodes  restrict the occupied-IP lookup to these nodes
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
