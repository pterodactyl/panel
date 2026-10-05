<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Tag;
use Pterodactyl\Validation\TagRules;

class UpdateTagRequest extends StoreTagRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminTagsUpdate];
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return $this->withSlugRule(TagRules::rules($this->parameter('tag', Tag::class)));
    }
}
