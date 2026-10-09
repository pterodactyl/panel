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
     * Update an egg from request-validated attributes.
     *
     * @param  EggCreationData  $data
     */
    public function update(Egg $egg, array $data): Egg
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);

        // TODO(dane): Once the admin UI is done being reworked and this is exposed
        //  in said UI, remove this so that you can actually update the denylist.
        unset($data['file_denylist']);

        DB::transaction(function () use ($egg, $data, $extensions): void {
            $egg->update($data);
            $this->extensions->save($egg, $extensions);
        });

        return $egg;
    }
}
