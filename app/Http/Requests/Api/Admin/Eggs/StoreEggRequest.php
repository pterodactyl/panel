<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Actions\Eggs\CreateEgg;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Egg;

class StoreEggRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminEggsCreate];
    }

    /**
     * Validation rules for creating a top-level egg.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateEgg::rules();
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return Egg::class;
    }

    /**
     * A zero config_from is the legacy "no parent egg" sentinel; treat it as null. The API
     * drops any entry of features or file_denylist that is not a string, and reads a list
     * sent as an object as empty, where the action would refuse them.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('config_from') && filter_var($this->input('config_from'), FILTER_VALIDATE_INT) === 0) {
            $this->merge(['config_from' => null]);
        }

        foreach (['features', 'file_denylist'] as $field) {
            $values = $this->input($field);
            if (is_array($values)) {
                $this->merge([$field => array_is_list($values) ? array_values(array_filter($values, is_string(...))) : []]);
            }
        }
    }
}
