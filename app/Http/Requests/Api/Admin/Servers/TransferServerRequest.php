<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Transfers\InitiateTransfer;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Server;
use Pterodactyl\Validation\Concerns\ValidatesArrayExistence;

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
        return InitiateTransfer::rules($this->parameter('server', Server::class));
    }

    /**
     * Chosen allocations must exist, belong to the target node and be unassigned.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateArrayExistence($validator, 'allocation_additional', Allocation::query());
            InitiateTransfer::validateAllocations($validator);
        }];
    }

    /**
     * The validated additional allocation ids, as integers.
     *
     * @return list<int>
     */
    public function additionalAllocations(): array
    {
        $validated = $this->safe();

        return array_map(fn (int|string $key): int => $validated->integer('allocation_additional.'.$key), array_keys($validated->array('allocation_additional')));
    }
}
