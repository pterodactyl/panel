<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Actions\Tags\CreateTag;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Tag;

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
        return $this->writable(CreateTag::rules($this->parameter('tag', Tag::class)));
    }
}
