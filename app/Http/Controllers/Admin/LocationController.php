<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Admin;

use App\Models\Location;
use App\Exceptions\DisplayException;
use App\Http\Controllers\Controller;
use Prologue\Alerts\AlertsMessageBag;
use App\Http\Requests\Admin\LocationFormRequest;
use App\Services\Locations\LocationUpdateService;
use App\Services\Locations\LocationCreationService;
use App\Services\Locations\LocationDeletionService;
use App\Contracts\Repository\LocationRepositoryInterface;

class LocationController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Services\Locations\LocationCreationService
     */
    protected $creationService;

    /**
     * @var \App\Services\Locations\LocationDeletionService
     */
    protected $deletionService;

    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Locations\LocationUpdateService
     */
    protected $updateService;

    /**
     * LocationController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                             $alert
     * @param \App\Services\Locations\LocationCreationService       $creationService
     * @param \App\Services\Locations\LocationDeletionService       $deletionService
     * @param \App\Contracts\Repository\LocationRepositoryInterface $repository
     * @param \App\Services\Locations\LocationUpdateService         $updateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository,
        LocationUpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Return the location overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.locations.index', [
            'locations' => $this->repository->getAllWithDetails(),
        ]);
    }

    /**
     * Return the location view page.
     *
     * @param int $id
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function view($id)
    {
        return view('admin.locations.view', [
            'location' => $this->repository->getWithNodes($id),
        ]);
    }

    /**
     * Handle request to create new location.
     *
     * @param \App\Http\Requests\Admin\LocationFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(LocationFormRequest $request)
    {
        $location = $this->creationService->handle($request->normalize());
        $this->alert->success('Location was created successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Handle request to update or delete location.
     *
     * @param \App\Http\Requests\Admin\LocationFormRequest $request
     * @param \App\Models\Location                         $location
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function update(LocationFormRequest $request, Location $location)
    {
        if ($request->input('action') === 'delete') {
            return $this->delete($location);
        }

        $this->updateService->handle($location->id, $request->normalize());
        $this->alert->success('Location was updated successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Delete a location from the system.
     *
     * @param \App\Models\Location $location
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \App\Exceptions\DisplayException
     */
    public function delete(Location $location)
    {
        try {
            $this->deletionService->handle($location->id);

            return redirect()->route('admin.locations');
        } catch (DisplayException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.locations.view', $location->id);
    }
}
