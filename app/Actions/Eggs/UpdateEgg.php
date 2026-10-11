<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Facades\DB;
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
     * @param  ActionInput  $input
     */
    public function update(Egg $egg, array $input): Egg
    {
        $attributes = CreateEgg::attributes($input);
        $extensions = ValidatedExtensionValues::of($input['extensions'] ?? null);

        // TODO(dane): Once the admin UI is done being reworked and this is exposed
        //  in said UI, remove this so that you can actually update the denylist.
        unset($attributes['file_denylist']);

        DB::transaction(function () use ($egg, $attributes, $extensions): void {
            $egg->update($attributes);
            $this->extensions->save($egg, $extensions);
        });

        return $egg;
    }
}
