<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Rules\TagSlug;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Support\ValidationRuleSubset;
use Pterodactyl\Validation\TagRules;

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
        return $this->withSlugRule(TagRules::rules());
    }

    /**
     * Model attributes for the tag. The color stays absent when the request
     * omits it so an update leaves the stored value alone.
     *
     * @return TagData
     */
    public function payload(): array
    {
        $payload = [
            'name' => $this->string('name')->toString(),
            'slug' => $this->string('slug')->toString(),
        ];

        if ($this->exists('color')) {
            $payload['color'] = JsonValueGuard::nullableString($this->input('color'));
        }

        return $payload;
    }

    /**
     * Narrow the model rules to the writable fields and forbid numeric slugs, so a
     * leftover legacy nest id can be told apart from a real tag and a slug can never
     * shadow a tag id (see TagSlug).
     *
     * @param  NormalizedValidationRules  $rules
     * @return NormalizedValidationRules
     */
    protected function withSlugRule(array $rules): array
    {
        $rules = ValidationRuleSubset::select($rules, ['name', 'slug', 'color']);
        $rules['slug'] = array_merge($rules['slug'], [new TagSlug]);

        return $rules;
    }
}
