<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Pterodactyl\Contracts\Tags\CreatesTags;
use Pterodactyl\Models\Tag;

final readonly class CreateTag implements CreatesTags
{
    /**
     * Create a new tag from validated attributes.
     *
     * @param  TagData  $data
     */
    public function create(array $data): Tag
    {
        return Tag::query()->create($data);
    }
}
