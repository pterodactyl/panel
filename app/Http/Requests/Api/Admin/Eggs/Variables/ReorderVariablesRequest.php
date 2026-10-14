<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Pterodactyl\Actions\Eggs\Variables\ReorderEggVariables;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

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
        return ReorderEggVariables::rules();
    }

    /**
     * The validated variable ids, in their new order.
     *
     * @return list<int>
     */
    public function order(): array
    {
        $validated = $this->safe();

        return array_map(fn (int|string $key): int => $validated->integer('order.'.$key), array_keys($validated->array('order')));
    }
}
