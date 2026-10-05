<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Concerns\ValidatesArrayExistence;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;

class TransferServerRequest extends ServerWriteRequest
{
    use ValidatesArrayExistence;

    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for transferring a server to another node.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $server = $this->parameter('server', Server::class);

        return [
            'node_id' => [
                'required',
                'integer',
                'exists:nodes,id',
                Rule::notIn([$server->node_id]),
            ],
            'allocation_id' => ['required', 'bail', 'unique:servers', 'exists:allocations,id'],
            'allocation_additional' => ['nullable', 'array', 'list'],
            'allocation_additional.*' => ['required', Rule::anyOf([['integer:strict'], ['string']]), 'integer'],
        ];
    }

    /**
     * Chosen allocations must belong to the target node and be unassigned.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateArrayExistence($validator, 'allocation_additional', Allocation::query());

            // Skip cross-field checks if the basic rules already failed.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $nodeId = $this->integer('node_id');
            $ids = array_unique([$this->integer('allocation_id'), ...$this->additionalAllocations()]);
            $count = Allocation::query()
                ->whereIn('id', $ids)
                ->where('node_id', $nodeId)
                ->whereNull('server_id')
                ->count();

            if ($count !== count($ids)) {
                $validator->errors()->add('allocation_id', 'The selected allocation must be an unassigned allocation on the target node.');
            }
        }];
    }

    /**
     * Normalize the validated transfer payload for the controller.
     *
     * @return ServerTransferData
     */
    public function payload(): array
    {
        return [
            'node_id' => $this->integer('node_id'),
            'allocation_id' => $this->integer('allocation_id'),
            'allocation_additional' => $this->additionalAllocations(),
        ];
    }

    /** @return list<int> */
    private function additionalAllocations(): array
    {
        return JsonValueGuard::normalizedIntegerList(JsonValueGuard::integerStringList($this->input('allocation_additional') ?? []));
    }
}
