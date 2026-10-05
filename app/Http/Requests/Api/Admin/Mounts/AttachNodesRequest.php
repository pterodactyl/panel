<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesArrayExistence;
use Pterodactyl\Models\Node;
use Pterodactyl\Support\JsonValueGuard;

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
        return [
            'nodes' => ['required', 'array', 'list', 'min:1'],
            'nodes.*' => [Rule::anyOf([['integer:strict'], ['string']]), 'required', 'integer'],
        ];
    }

    /** @return list<int> */
    public function nodes(): array
    {
        return JsonValueGuard::normalizedIntegerList(JsonValueGuard::integerStringList($this->validated('nodes')));
    }
}
