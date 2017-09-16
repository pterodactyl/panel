<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     *
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $repository
     * @param \Pterodactyl\Services\Locations\LocationDeletionService       $deletionService
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
            trans('command/messages.location.ask_short'), $this->locations->pluck('short')->toArray()
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
