<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Command;
use Pterodactyl\Services\Locations\LocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class DeleteLocationCommand extends Command
{
    /**
     * @var \Pterodactyl\Services\Locations\LocationDeletionService
     */
    protected $deletionService;

    /**
     * @var string
     */
    protected $description = 'Deletes a location from the Panel.';

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $locations;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $signature = 'p:location:delete {--short= : The short code of the location to delete.}';

    /**
     * DeleteLocationCommand constructor.
     */
    public function __construct(
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->deletionService = $deletionService;
        $this->repository = $repository;
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
