<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Mounts\UpdatesMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateMount implements UpdatesMounts
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Mount $mount, array $input): Mount
    {
        $validated = Validator::validate($input, CreateMount::rules($mount));
        // Blank strings are null, as they are in a request, so a blank description is cleared.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // The description and flags it is not given keep their stored values.
        DB::transaction(function () use ($mount, $validated, $extensions): void {
            $mount->forceFill($validated)->save();
            $this->extensions->save($mount, $extensions);
        });

        return $mount;
    }
}
