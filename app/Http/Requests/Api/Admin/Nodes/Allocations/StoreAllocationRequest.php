<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class StoreAllocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminAllocationsCreate];
    }

    /**
     * Validation rules for assigning new allocations to a node.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'ip' => ['required', 'array', 'list'],
            'ip.*' => ['string'],
            'alias' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ports' => ['required', 'array', 'list'],
            'ports.*' => ['string'],
        ];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'ip' => 'IP Address',
            'alias' => 'IP Alias',
            'ports' => 'Ports',
        ];
    }

    /**
     * The validated ports and alias under the action's field names. The action creates the
     * allocations of one address at a time, so the controller adds each of addresses() in turn.
     *
     * @return array{allocation_ports: list<string>, allocation_alias: string|null}
     */
    public function actionInput(): array
    {
        $validated = $this->safe();

        return [
            'allocation_ports' => $this->strings('ports'),
            'allocation_alias' => $validated->filled('alias') ? $validated->string('alias')->toString() : null,
        ];
    }

    /**
     * The validated addresses, each an IP address or CIDR range.
     *
     * @return list<string>
     */
    public function addresses(): array
    {
        return $this->strings('ip');
    }

    /**
     * The validated strings listed under a field.
     *
     * @return list<string>
     */
    private function strings(string $field): array
    {
        $validated = $this->safe();

        return array_map(fn (int|string $key): string => $validated->string($field.'.'.$key)->toString(), array_keys($validated->array($field)));
    }
}
