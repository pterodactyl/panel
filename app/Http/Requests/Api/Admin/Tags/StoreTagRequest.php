<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Illuminate\Support\Arr;
use Pterodactyl\Actions\Tags\CreateTag;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class StoreTagRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminTagsCreate];
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return $this->writable(CreateTag::rules());
    }

    /**
     * Narrow a tag's rules to the fields the API writes; it never sets the legacy nest id.
     *
     * @param  NormalizedValidationRules  $rules
     * @return NormalizedValidationRules
     */
    protected function writable(array $rules): array
    {
        return Arr::only($rules, ['name', 'slug', 'color']);
    }
}
