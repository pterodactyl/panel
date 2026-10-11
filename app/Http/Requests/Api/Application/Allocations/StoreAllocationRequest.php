<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Allocations;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;

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
     * The validated input under the action's field names: ip, ports and alias are its
     * allocation_ip, allocation_ports and allocation_alias.
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        $input = parent::actionInput();

        return [
            'allocation_ip' => $input['ip'] ?? null,
            'allocation_ports' => $input['ports'] ?? null,
            'allocation_alias' => $input['alias'] ?? null,
        ];
    }
}
