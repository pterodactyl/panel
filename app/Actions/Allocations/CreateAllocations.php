<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Allocations;

use Exception;
use Illuminate\Support\Facades\DB;
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
     * @param  AllocationAssignmentData  $data
     *
     * @throws DisplayException
     * @throws CidrOutOfRangeException
     * @throws InvalidPortMappingException
     * @throws PortOutOfRangeException
     * @throws TooManyPortsInRangeException
     */
    public function create(Node $node, array $data): void
    {
        $segments = explode('/', $data['allocation_ip'], 2);
        if (isset($segments[1])) {
            throw_if(! ctype_digit($segments[1]) || ($segments[1] > self::CIDR_MIN_BITS || $segments[1] < self::CIDR_MAX_BITS), CidrOutOfRangeException::class);
        }

        $underlying = $data['allocation_ip'];
        try {
            $underlying = gethostbyname($data['allocation_ip']);
            $parsed = Network::parse($underlying);
        } catch (Exception $exception) {
            throw new DisplayException("Could not parse provided allocation IP address ({$underlying}): {$exception->getMessage()}", $exception);
        }

        DB::transaction(function () use ($node, $data, $parsed): void {
            foreach ($parsed as $ip) {
                foreach ($data['allocation_ports'] as $port) {
                    // SAFETY: the allocation contract limits ports to integers and strings before this syntax parser.
                    $portText = (string) $port;
                    $isPort = ctype_digit($portText);
                    $isRange = preg_match(self::PORT_RANGE_REGEX, $portText, $matches) === 1;
                    throw_if(! $isPort && ! $isRange, InvalidPortMappingException::class, $port);

                    if ($isRange) {
                        // SAFETY: PORT_RANGE_REGEX captures two decimal integer strings.
                        $start = (int) $matches[1];
                        // SAFETY: PORT_RANGE_REGEX captures two decimal integer strings.
                        $end = (int) $matches[2];
                        $block = range($start, $end);
                        throw_if(count($block) > self::PORT_RANGE_LIMIT, TooManyPortsInRangeException::class);
                        throw_if($start <= self::PORT_FLOOR || $end > self::PORT_CEIL, PortOutOfRangeException::class);
                    } else {
                        // SAFETY: ctype_digit() above proves a decimal port string in the non-range branch.
                        $portNumber = (int) $portText;
                        throw_if($portNumber <= self::PORT_FLOOR || $portNumber > self::PORT_CEIL, PortOutOfRangeException::class);
                        $block = [$portNumber];
                    }

                    $insertData = [];
                    foreach ($block as $unit) {
                        $insertData[] = [
                            'node_id' => $node->id,
                            'ip' => $ip->__toString(),
                            'port' => $unit,
                            'ip_alias' => $data['allocation_alias'] ?? null,
                            'server_id' => null,
                        ];
                    }

                    Allocation::query()->insertOrIgnore($insertData);
                }
            }
        });
    }
}
