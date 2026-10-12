<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use IPTools\Network;
use Pterodactyl\Contracts\Allocations\CreatesAllocations;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException;
use Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;

final readonly class CreateAllocations implements CreatesAllocations
{
    public const int CIDR_MAX_BITS = 25;

    public const int CIDR_MIN_BITS = 32;

    public const int PORT_FLOOR = 1024;

    public const int PORT_CEIL = 65535;

    public const int PORT_RANGE_LIMIT = 1000;

    public const string PORT_RANGE_REGEX = '/^(\d{4,5})-(\d{4,5})$/';

    /**
     * Rules every allocation note must pass, wherever it is set.
     *
     * @var list<string>
     */
    public const array NOTES = ['nullable', 'string', 'max:256'];

    /**
     * The allocation exists and no server holds it, for requests that hand allocations to a server.
     */
    public static function unassigned(): Exists
    {
        return Rule::exists('allocations', 'id')->where(function (Builder $query): void {
            $query->whereNull('server_id');
        });
    }

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'allocation_ip' => ['required', 'string'],
            'allocation_ports' => ['required', 'array'],
            'allocation_ports.*' => [Rule::anyOf([['integer:strict'], ['string']])],
            'allocation_alias' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function create(Node $node, array $input): void
    {
        $validated = Validator::make($input, self::rules())->safe();
        $ip = $validated->string('allocation_ip')->toString();
        $alias = $validated->filled('allocation_alias') ? $validated['allocation_alias'] : null;

        // A port given as a number is read like one given as text: a single port or a range.
        $ports = array_map(fn (int|string $key): string => $validated->string('allocation_ports.'.$key)->toString(), array_keys($validated->array('allocation_ports')));

        $segments = explode('/', $ip, 2);
        if (isset($segments[1])) {
            throw_if(! ctype_digit($segments[1]) || ($segments[1] > self::CIDR_MIN_BITS || $segments[1] < self::CIDR_MAX_BITS), CidrOutOfRangeException::class);
        }

        $underlying = $ip;
        try {
            $underlying = gethostbyname($ip);
            $parsed = Network::parse($underlying);
        } catch (Exception $exception) {
            report($exception);

            throw new DisplayException("Could not parse provided allocation IP address ({$underlying}): {$exception->getMessage()}", $exception);
        }

        DB::transaction(function () use ($node, $ports, $alias, $parsed): void {
            foreach ($parsed as $ip) {
                foreach ($ports as $port) {
                    $isPort = ctype_digit($port);
                    $isRange = preg_match(self::PORT_RANGE_REGEX, $port, $matches) === 1;
                    throw_if(! $isPort && ! $isRange, InvalidPortMappingException::class, $port);

                    if ($isRange) {
                        $start = (int) $matches[1];
                        $end = (int) $matches[2];
                        $block = range($start, $end);
                        throw_if(count($block) > self::PORT_RANGE_LIMIT, TooManyPortsInRangeException::class);
                        throw_if($start <= self::PORT_FLOOR || $end > self::PORT_CEIL, PortOutOfRangeException::class);
                    } else {
                        $portNumber = (int) $port;
                        throw_if($portNumber <= self::PORT_FLOOR || $portNumber > self::PORT_CEIL, PortOutOfRangeException::class);
                        $block = [$portNumber];
                    }

                    $insertData = [];
                    foreach ($block as $unit) {
                        $insertData[] = [
                            'node_id' => $node->id,
                            'ip' => $ip->__toString(),
                            'port' => $unit,
                            'ip_alias' => $alias,
                            'server_id' => null,
                        ];
                    }

                    Allocation::query()->insertOrIgnore($insertData);
                }
            }
        });
    }
}
