<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Illuminate\Validation\Rule;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Support\JsonValueGuard;

class ReorderVariablesRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggVariablesUpdate];
    }

    /**
     * Array position of each variable id becomes its persisted sort_order.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'list'],
            'order.*' => ['required', Rule::anyOf([['integer:strict'], ['string']]), 'integer', 'distinct'],
        ];
    }

    /** @return list<int> */
    public function order(): array
    {
        return JsonValueGuard::normalizedIntegerList(JsonValueGuard::integerStringList($this->validated('order')));
    }
}
