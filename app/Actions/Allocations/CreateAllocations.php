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

    /**
     * The fields of the allocations to create on one address: an IP address, CIDR range or
     * hostname, the ports and port ranges ("25565-25570") to open on it, and an optional alias.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'allocation_ip' => ['required', 'string'],
            'allocation_ports' => ['required', 'array'],
            'allocation_ports.*' => [Rule::anyOf([['integer:strict'], ['string']])],
            'allocation_alias' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Validates allocation input and returns the address, ports and alias it describes. Each
     * port comes back as written, for create() to read as a single port or a range.
     *
     * @param  array<string, mixed>  $input
     * @return array{ip: string, ports: list<string>, ip_alias: string|null}
     */
    public static function attributes(array $input): array
    {
        $input = Validator::make($input, self::rules())->safe();

        $ports = [];
        foreach (array_keys($input->array('allocation_ports')) as $key) {
            $ports[] = $input->string('allocation_ports.'.$key)->toString();
        }

        return [
            'ip' => $input->string('allocation_ip')->toString(),
            'ports' => $ports,
            'ip_alias' => $input->filled('allocation_alias') ? $input->string('allocation_alias')->toString() : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws DisplayException
     * @throws CidrOutOfRangeException
     * @throws InvalidPortMappingException
     * @throws PortOutOfRangeException
     * @throws TooManyPortsInRangeException
     */
    public function create(Node $node, array $input): void
    {
        $attributes = self::attributes($input);

        $segments = explode('/', $attributes['ip'], 2);
        if (isset($segments[1])) {
            throw_if(! ctype_digit($segments[1]) || ($segments[1] > self::CIDR_MIN_BITS || $segments[1] < self::CIDR_MAX_BITS), CidrOutOfRangeException::class);
        }

        $underlying = $attributes['ip'];
        try {
            $underlying = gethostbyname($attributes['ip']);
            $parsed = Network::parse($underlying);
        } catch (Exception $exception) {
            report($exception);

            throw new DisplayException("Could not parse provided allocation IP address ({$underlying}): {$exception->getMessage()}", $exception);
        }

        DB::transaction(function () use ($node, $attributes, $parsed): void {
            foreach ($parsed as $ip) {
                foreach ($attributes['ports'] as $port) {
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
                            'ip_alias' => $attributes['ip_alias'],
                            'server_id' => null,
                        ];
                    }

                    Allocation::query()->insertOrIgnore($insertData);
                }
            }
        });
    }
}
