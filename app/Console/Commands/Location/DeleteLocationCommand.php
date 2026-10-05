<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Contracts\Locations\DeletesLocations;
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;
use Pterodactyl\Models\Location;

#[Description('Deletes a location from the Panel.')]
#[Signature('p:location:delete {--short= : The short code of the location to delete.}')]
class DeleteLocationCommand extends Command
{
    /** @var Collection<int, Location> */
    protected Collection $allLocations;

    /**
     * Respond to the command request.
     *
     * @throws HasActiveNodesException
     */
    public function handle(DeletesLocations $locations): void
    {
        $this->allLocations ??= Location::all();
        $short = $this->option('short') ?? $this->anticipate(
            trans('command/messages.location.ask_short'),
            $this->allLocations->pluck('short')->toArray()
        );

        $location = $this->allLocations->where('short', $short)->first();
        if ($location === null) {
            $this->error(trans('command/messages.location.no_location_found'));
            if ($this->input->isInteractive()) {
                $this->handle($locations);
            }

            return;
        }

        $locations->delete($location);
        $this->line(trans('command/messages.location.deleted'));
    }
}
