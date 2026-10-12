<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Eggs\UpdatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;

final readonly class UpdateEgg implements UpdatesEggs
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Update an egg with the fields it is created with.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(Egg $egg, array $input): Egg
    {
        // A parent egg of 0 is the legacy "no parent egg" sentinel.
        if (filter_var($input['config_from'] ?? null, FILTER_VALIDATE_INT) === 0) {
            $input['config_from'] = null;
        }

        $validated = Validator::validate($input, CreateEgg::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // TODO(dane): Once the admin UI is done being reworked and this is exposed
        //  in said UI, remove this so that you can actually update the denylist.
        unset($validated['file_denylist']);

        DB::transaction(function () use ($egg, $validated, $extensions): void {
            // An egg given no description, outgoing IP flag or features gets none. The parent
            // egg and config blocks it is not given keep their stored values.
            $egg->update([
                ...$validated,
                'description' => $validated['description'] ?? null,
                'docker_images' => CreateEgg::dockerImages(Arr::string($validated, 'docker_images')),
                'force_outgoing_ip' => $validated['force_outgoing_ip'] ?? false,
                'features' => $validated['features'] ?? [],
            ]);
            $this->extensions->save($egg, $extensions);
        });

        return $egg;
    }
}
