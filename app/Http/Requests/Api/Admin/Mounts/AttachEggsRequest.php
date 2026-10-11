<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Actions\Mounts\AttachEggsToMount;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesArrayExistence;
use Pterodactyl\Models\Egg;

class AttachEggsRequest extends AdminApiRequest
{
    use ValidatesArrayExistence;

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateArrayExistence($validator, 'eggs', Egg::query())];
    }

    public function permissions(): array
    {
        return [Permissions::AdminMountsUpdate];
    }

    /**
     * Validation rules for attaching eggs to a mount.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return AttachEggsToMount::rules();
    }

    /**
     * The validated egg ids, as integers.
     *
     * @return list<int>
     */
    public function eggs(): array
    {
        $validated = $this->safe();

        return array_map(fn (int|string $key): int => $validated->integer('eggs.'.$key), array_keys($validated->array('eggs')));
    }
}
