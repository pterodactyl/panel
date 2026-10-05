<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use UnexpectedValueException;

class StoreAllocationRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_ALLOCATIONS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'ip' => ['required', 'string'],
            'alias' => ['sometimes', 'nullable', 'string', 'max:191'],
            'ports' => ['required', 'array'],
            'ports.*' => ['string'],
        ];
    }

    /**
     * @return AllocationAssignmentData
     */
    public function payload(): array
    {
        $data = parent::validated();
        $ip = $data['ip'] ?? null;
        $ports = $data['ports'] ?? null;
        $alias = $data['alias'] ?? null;

        throw_if(! is_string($ip) || ! is_array($ports) || ! array_is_list($ports), UnexpectedValueException::class, 'Validated allocation data has an invalid structure.');

        $normalizedPorts = [];
        foreach ($ports as $port) {
            throw_unless(is_string($port), UnexpectedValueException::class, 'Validated allocation ports must be strings.');

            $normalizedPorts[] = $port;
        }

        throw_if($alias !== null && ! is_string($alias), UnexpectedValueException::class, 'Validated allocation aliases must be strings or null.');

        return [
            'allocation_ip' => $ip,
            'allocation_ports' => $normalizedPorts,
            'allocation_alias' => $alias,
        ];
    }
}
