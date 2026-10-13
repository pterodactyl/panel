<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Eggs\DeletesEggs;
use Pterodactyl\Exceptions\Service\Egg\HasChildrenException;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Egg;

final class DeleteEgg implements DeletesEggs
{
    public function delete(Egg $egg): void
    {
        if ($egg->servers()->exists()) {
            throw new HasActiveServersException(trans('exceptions.egg.delete_has_servers'));
        }

        if ($egg->configuredChildren()->exists()) {
            throw new HasChildrenException(trans('exceptions.egg.has_children'));
        }

        DB::transaction(function () use ($egg): void {
            // Tag attachments are polymorphic, so no foreign key removes them with the egg.
            $egg->tags()->detach();
            $egg->delete();
        });
    }
}
