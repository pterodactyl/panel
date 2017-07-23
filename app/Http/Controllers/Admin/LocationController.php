<?php
/**
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

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Models\Location;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\LocationRequest;
use Pterodactyl\Services\LocationService;

class LocationController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\\LocationService
     */
    protected $service;

    /**
     * LocationController constructor.
     *
     * @param  \Prologue\Alerts\AlertsMessageBag                             $alert
     * @param  \Pterodactyl\Contracts\Repository\LocationRepositoryInterface $repository
     * @param  \Pterodactyl\Services\LocationService                         $service
     */
    public function __construct(
        AlertsMessageBag $alert,
        LocationRepositoryInterface $repository,
        LocationService $service
    ) {
        $this->alert = $alert;
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Return the location overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.locations.index', [
            'locations' => $this->repository->allWithDetails(),
        ]);
    }

    /**
     * Return the location view page.
     *
     * @param  int $id
     * @return \Illuminate\View\View
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
     * @param  \Pterodactyl\Http\Requests\Admin\LocationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     * @throws \Watson\Validating\ValidationException
     */
    public function create(LocationRequest $request)
    {
        $location = $this->service->create($request->normalize());
        $this->alert->success('Location was created successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Handle request to update or delete location.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\LocationRequest $request
     * @param  \Pterodactyl\Models\Location                     $location
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     * @throws \Watson\Validating\ValidationException
     */
    public function update(LocationRequest $request, Location $location)
    {
        if ($request->input('action') === 'delete') {
            return $this->delete($location);
        }

        $this->service->update($location->id, $request->normalize());
        $this->alert->success('Location was updated successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Delete a location from the system.
     *
     * @param  \Pterodactyl\Models\Location $location
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(Location $location)
    {
        try {
            $this->service->delete($location->id);

            return redirect()->route('admin.locations');
        } catch (DisplayException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.locations.view', $location->id);
    }
}
