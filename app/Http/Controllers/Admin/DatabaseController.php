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

use Pterodactyl\Models\Location;
use Pterodactyl\Models\DatabaseHost;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\DatabaseHostService;
use Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest;

class DatabaseController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Models\DatabaseHost
     */
    protected $hostModel;

    /**
     * @var \Pterodactyl\Models\Location
     */
    protected $locationModel;

    /**
     * @var \Pterodactyl\Services\DatabaseHostService
     */
    protected $service;

    /**
     * DatabaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag         $alert
     * @param \Pterodactyl\Models\DatabaseHost          $hostModel
     * @param \Pterodactyl\Models\Location              $locationModel
     * @param \Pterodactyl\Services\DatabaseHostService $service
     */
    public function __construct(
        AlertsMessageBag $alert,
        DatabaseHost $hostModel,
        Location $locationModel,
        DatabaseHostService $service
    ) {
        $this->alert = $alert;
        $this->hostModel = $hostModel;
        $this->locationModel = $locationModel;
        $this->service = $service;
    }

    /**
     * Display database host index.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.databases.index', [
            'locations' => $this->locationModel->with('nodes')->get(),
            'hosts' => $this->hostModel->withCount('databases')->with('node')->get(),
        ]);
    }

    /**
     * Display database host to user.
     *
     * @param  \Pterodactyl\Models\DatabaseHost  $host
     * @return \Illuminate\View\View
     */
    public function view(DatabaseHost $host)
    {
        $host->load('databases.server');

        return view('admin.databases.view', [
            'locations' => $this->locationModel->with('nodes')->get(),
            'host' => $host,
        ]);
    }

    /**
     * Handle request to create a new database host.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(DatabaseHostFormRequest $request)
    {
        try {
            $host = $this->service->create($request->normalize());
            $this->alert->success('Successfully created a new database host on the system.')->flash();

            return redirect()->route('admin.databases.view', $host->id);
        } catch (\PDOException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.databases');
    }

    /**
     * Handle updating database host.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @param  \Pterodactyl\Models\DatabaseHost                         $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function update(DatabaseHostFormRequest $request, DatabaseHost $host)
    {
        if ($request->input('action') === 'delete') {
            return $this->delete($host);
        }

        try {
            $host = $this->service->update($host->id, $request->normalize());
            $this->alert->success('Database host was updated successfully.')->flash();
        } catch (\PDOException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.databases.view', $host->id);
    }

    /**
     * Handle request to delete a database host.
     *
     * @param  \Pterodactyl\Models\DatabaseHost  $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(DatabaseHost $host)
    {
        $this->service->delete($host->id);
        $this->alert->success('The requested database host has been deleted from the system.')->flash();

        return redirect()->route('admin.databases');
    }
}
