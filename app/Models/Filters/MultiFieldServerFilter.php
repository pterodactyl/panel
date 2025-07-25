<?php

namespace Pterodactyl\Models\Filters;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class MultiFieldServerFilter implements Filter
{
    /**
     * If we detect that the value matches an IPv4 address we will use a different type of filtering
     * to look at the allocations.
     */
    private const IPV4_REGEX = '/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}(\:\d{1,5})?$/';

    /**
     * A multi-column filter for the servers table that allows you to pass in a single value and
     * search across multiple columns. This allows us to provide a very generic search ability for
     * the frontend.
     *
     * @param string $value
     */
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($query->getQuery()->from !== 'servers') {
            throw new \BadMethodCallException('Cannot use the MultiFieldServerFilter against a non-server model.');
        }

        if (preg_match(self::IPV4_REGEX, $value) || preg_match('/^:\d{1,5}$/', $value)) {
            $query
                // Only select the server values, otherwise you'll end up merging the allocation and
                // server objects together, resulting in incorrect behavior and returned values.
                ->select('servers.*')
                ->join('allocations', 'allocations.server_id', '=', 'servers.id')
                ->where(function (Builder $builder) use ($value) {
                    $parts = explode(':', $value);

                    $builder->when(
                        !Str::startsWith($value, ':'),
                        // When the string does not start with a ":" it means we're looking for an IP or IP:Port
                        // combo, so use a query to handle that.
                        function (Builder $builder) use ($parts) {
                            $builder->orWhere('allocations.ip', $parts[0]);
                            if (!is_null($parts[1] ?? null)) {
                                $builder->where('allocations.port', 'LIKE', "{$parts[1]}%");
                            }
                        },
                        // Otherwise, just try to search for that specific port in the allocations.
                        function (Builder $builder) use ($value) {
                            $builder->orWhere('allocations.port', 'LIKE', substr($value, 1) . '%');
                        }
                    );
                })
                ->groupBy('servers.id');

            return;
        }

        $query
            ->where(function (Builder $builder) use ($value) {
                $builder->where('servers.uuid', $value)
                    ->orWhere('servers.uuid', 'LIKE', "$value%")
                    ->orWhere('servers.uuidShort', $value)
                    ->orWhere('servers.external_id', $value)
                    ->orWhereRaw('LOWER(servers.name) LIKE ?', ["%$value%"]);
            });
    }
}
