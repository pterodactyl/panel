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

use Pterodactyl\Models\Service;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Services\ServiceUpdateService;
use Pterodactyl\Services\Services\ServiceCreationService;
use Pterodactyl\Services\Services\ServiceDeletionService;
use Pterodactyl\Exceptions\Services\HasActiveServersException;
use Pterodactyl\Http\Requests\Admin\Service\ServiceFormRequest;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Service\ServiceFunctionsFormRequest;

class ServiceController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Services\ServiceCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Services\ServiceDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\ServiceUpdateService
     */
    protected $updateService;

    public function __construct(
        AlertsMessageBag $alert,
        ServiceCreationService $creationService,
        ServiceDeletionService $deletionService,
        ServiceRepositoryInterface $repository,
        ServiceUpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Display service overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.services.index', [
            'services' => $this->repository->getWithOptions(),
        ]);
    }

    /**
     * Display create service page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.services.new');
    }

    /**
     * Return base view for a service.
     *
     * @param  int $service
     * @return \Illuminate\View\View
     */
    public function view($service)
    {
        return view('admin.services.view', [
            'service' => $this->repository->getWithOptionServers($service),
        ]);
    }

    /**
     * Return function editing view for a service.
     *
     * @param  \Pterodactyl\Models\Service $service
     * @return \Illuminate\View\View
     */
    public function viewFunctions(Service $service)
    {
        return view('admin.services.functions', ['service' => $service]);
    }

    /**
     * Handle post action for new service.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\Service\ServiceFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ServiceFormRequest $request)
    {
        $service = $this->creationService->handle($request->normalize());
        $this->alert->success(trans('admin/services.notices.service_created', ['name' => $service->name]))->flash();

        return redirect()->route('admin.services.view', $service->id);
    }

    /**
     * Edits configuration for a specific service.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\Service\ServiceFormRequest $request
     * @param  \Pterodactyl\Models\Service                                 $service
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ServiceFormRequest $request, Service $service)
    {
        $this->updateService->handle($service->id, $request->normalize());
        $this->alert->success(trans('admin/services.notices.service_updated'))->flash();

        return redirect()->route('admin.services.view', $service);
    }

    /**
     * Update the functions file for a service.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\Service\ServiceFunctionsFormRequest $request
     * @param  \Pterodactyl\Models\Service                                           $service
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateFunctions(ServiceFunctionsFormRequest $request, Service $service)
    {
        $this->updateService->handle($service->id, $request->normalize());
        $this->alert->success(trans('admin/services.notices.functions_updated'))->flash();

        return redirect()->route('admin.services.view.functions', $service->id);
    }

    /**
     * Delete a service from the panel.
     *
     * @param  \Pterodactyl\Models\Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Service $service)
    {
        try {
            $this->deletionService->handle($service->id);
            $this->alert->success(trans('admin/services.notices.service_deleted'))->flash();
        } catch (HasActiveServersException $exception) {
            $this->alert->danger($exception->getMessage())->flash();

            return redirect()->back();
        }

        return redirect()->route('admin.services');
    }
}
