<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Mounts\UpdatesMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateMount implements UpdatesMounts
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * @param  ActionInput  $input
     */
    public function update(Mount $mount, array $input): Mount
    {
        $attributes = CreateMount::attributes($input, $mount);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        DB::transaction(function () use ($mount, $attributes, $extensions): void {
            $mount->forceFill($attributes)->save();
            $this->extensions->save($mount, $extensions);
        });

        return $mount;
    }
}
