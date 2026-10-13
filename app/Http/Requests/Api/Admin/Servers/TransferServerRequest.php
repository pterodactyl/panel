<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Transfers\InitiateTransfer;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Concerns\ValidatesNodeAcceptsEgg;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Validation\Concerns\ValidatesArrayExistence;

class TransferServerRequest extends ServerWriteRequest
{
    use ValidatesArrayExistence;
    use ValidatesNodeAcceptsEgg;

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
        return [...InitiateTransfer::rules($this->parameter('server', Server::class)), 'ignore_tags' => ['sometimes', 'boolean']];
    }

    /**
     * Chosen allocations must exist, belong to the target node and be unassigned, and the
     * target node must accept the server's egg.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->validateArrayExistence($validator, 'allocation_additional', Allocation::query());
            InitiateTransfer::validateAllocations($validator);

            if ($validator->errors()->isEmpty()) {
                $egg = Egg::query()->findOrFail($this->parameter('server', Server::class)->egg_id);
                $this->validateNodeAcceptsEgg($validator, 'node_id', Node::query()->findOrFail($validator->safe()->integer('node_id')), $egg);
            }
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
