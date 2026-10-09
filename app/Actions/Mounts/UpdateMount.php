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
     * Update an existing mount from validated attributes.
     *
     * @param  MountData  $data
     */
    public function update(Mount $mount, array $data): Mount
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);

        DB::transaction(function () use ($mount, $data, $extensions): void {
            $mount->forceFill($data)->save();
            $this->extensions->save($mount, $extensions);
        });

        return $mount;
    }
}
