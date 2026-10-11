<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Mounts\AttachNodesToMount;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesArrayExistence;
use Pterodactyl\Models\Node;

class AttachNodesRequest extends AdminApiRequest
{
    use ValidatesArrayExistence;

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateArrayExistence($validator, 'nodes', Node::query())];
    }

    public function permissions(): array
    {
        return [Permissions::AdminMountsUpdate];
    }

    /**
     * Validation rules for attaching nodes to a mount.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return AttachNodesToMount::rules();
    }

    /**
     * The validated node ids, as integers.
     *
     * @return list<int>
     */
    public function nodes(): array
    {
        $validated = $this->safe();

        return array_map(fn (int|string $key): int => $validated->integer('nodes.'.$key), array_keys($validated->array('nodes')));
    }
}
