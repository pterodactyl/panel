<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Illuminate\Support\Arr;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

class GetDeployableNodesRequest extends GetNodesRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'memory' => ['required', 'integer', 'min:0'],
            'disk' => ['required', 'integer', 'min:0'],
            'location_ids' => ['sometimes', 'array'],
            'location_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array{memory: int, disk: int, location_ids: list<int>, per_page: int|null, page: int|null}
     */
    public function payload(): array
    {
        $validated = parent::validated();

        $rawLocationIds = Arr::get($validated, 'location_ids', []);
        throw_unless(is_array($rawLocationIds), UnexpectedValueException::class, 'The validated [location_ids] field must be an array.');

        $locationIds = [];
        foreach ($rawLocationIds as $rawLocationId) {
            $locationIds[] = JsonValueGuard::integer($rawLocationId);
        }

        return [
            'memory' => JsonValueGuard::integer($validated['memory']),
            'disk' => JsonValueGuard::integer($validated['disk']),
            'location_ids' => $locationIds,
            'per_page' => isset($validated['per_page']) ? JsonValueGuard::integer($validated['per_page']) : null,
            'page' => isset($validated['page']) ? JsonValueGuard::integer($validated['page']) : null,
        ];
    }
}
