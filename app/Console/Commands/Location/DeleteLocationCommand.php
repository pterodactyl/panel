<?php

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class DeleteLocationCommand extends Command
{
    protected $description = 'Deletes a location from the Panel.';

    protected $signature = 'p:location:delete {--short= : The short code of the location to delete.}';

    protected Collection $locations;

    /**
     * DeleteLocationCommand constructor.
     */
    public function __construct(
        private LocationDeletionService $deletionService,
        private LocationRepositoryInterface $repository
    ) {
        parent::__construct();
    }

    /**
     * Respond to the command request.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Location\HasActiveNodesException
     */
    public function handle()
    {
        $this->locations = $this->locations ?? $this->repository->all();
        $short = $this->option('short') ?? $this->anticipate(
            trans('command/messages.location.ask_short'),
            $this->locations->pluck('short')->toArray()
        );

        $location = $this->locations->where('short', $short)->first();
        if (is_null($location)) {
            $this->error(trans('command/messages.location.no_location_found'));
            if ($this->input->isInteractive()) {
                $this->handle();
            }

            return;
        }

        $this->deletionService->handle($location->id);
        $this->line(trans('command/messages.location.deleted'));
    }
}
