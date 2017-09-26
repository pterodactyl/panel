<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\Service;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Services\ServiceUpdateService;
use Pterodactyl\Services\Services\ServiceCreationService;
use Pterodactyl\Services\Services\ServiceDeletionService;
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
     * @param int $service
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
     * @param \Pterodactyl\Models\Service $service
     * @return \Illuminate\View\View
     */
    public function viewFunctions(Service $service)
    {
        return view('admin.services.functions', ['service' => $service]);
    }

    /**
     * Handle post action for new service.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\ServiceFormRequest $request
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
     * @param \Pterodactyl\Http\Requests\Admin\Service\ServiceFormRequest $request
     * @param \Pterodactyl\Models\Service                                 $service
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
     * @param \Pterodactyl\Http\Requests\Admin\Service\ServiceFunctionsFormRequest $request
     * @param \Pterodactyl\Models\Service                                          $service
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
     * @param \Pterodactyl\Models\Service $service
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Service $service)
    {
        $this->deletionService->handle($service->id);
        $this->alert->success(trans('admin/services.notices.service_deleted'))->flash();

        return redirect()->route('admin.services');
    }
}
