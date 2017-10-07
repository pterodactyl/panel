<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Models\Nest;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Services\NestUpdateService;
use Pterodactyl\Services\Services\NestCreationService;
use Pterodactyl\Services\Services\NestDeletionService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Service\StoreNestFormRequest;
use Pterodactyl\Http\Requests\Admin\Service\ServiceFunctionsFormRequest;

class ServiceController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Services\NestCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Services\NestDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\NestUpdateService
     */
    protected $updateService;

    /**
     * ServiceController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                         $alert
     * @param \Pterodactyl\Services\Services\NestCreationService        $creationService
     * @param \Pterodactyl\Services\Services\NestDeletionService        $deletionService
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface $repository
     * @param \Pterodactyl\Services\Services\NestUpdateService          $updateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        NestCreationService $creationService,
        NestDeletionService $deletionService,
        NestRepositoryInterface $repository,
        NestUpdateService $updateService
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
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => $this->repository->getWithCounts(),
        ]);
    }

    /**
     * Display create service page.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('admin.services.new');
    }

    /**
     * Return base view for a service.
     *
     * @param int $service
     * @return \Illuminate\View\View
     */
    public function view(int $service): View
    {
        return view('admin.services.view', [
            'service' => $this->repository->getWithOptionServers($service),
        ]);
    }

    /**
     * Return function editing view for a service.
     *
     * @param \Pterodactyl\Models\Nest $service
     * @return \Illuminate\View\View
     */
    public function viewFunctions(Nest $service): View
    {
        return view('admin.services.functions', ['service' => $service]);
    }

    /**
     * Handle post action for new service.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\StoreNestFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreNestFormRequest $request): RedirectResponse
    {
        $service = $this->creationService->handle($request->normalize());
        $this->alert->success(trans('admin/services.notices.service_created', ['name' => $service->name]))->flash();

        return redirect()->route('admin.services.view', $service->id);
    }

    /**
     * Edits configuration for a specific service.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\StoreNestFormRequest $request
     * @param \Pterodactyl\Models\Nest                                      $service
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(StoreNestFormRequest $request, Nest $service): RedirectResponse
    {
        $this->updateService->handle($service->id, $request->normalize());
        $this->alert->success(trans('admin/services.notices.service_updated'))->flash();

        return redirect()->route('admin.services.view', $service);
    }

    /**
     * Update the functions file for a service.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\ServiceFunctionsFormRequest $request
     * @param \Pterodactyl\Models\Nest                                             $service
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateFunctions(ServiceFunctionsFormRequest $request, Nest $service): RedirectResponse
    {
        $this->updateService->handle($service->id, $request->normalize());
        $this->alert->success(trans('admin/services.notices.functions_updated'))->flash();

        return redirect()->route('admin.services.view.functions', $service->id);
    }

    /**
     * Delete a service from the panel.
     *
     * @param \Pterodactyl\Models\Nest $service
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Nest $service): RedirectResponse
    {
        $this->deletionService->handle($service->id);
        $this->alert->success(trans('admin/services.notices.service_deleted'))->flash();

        return redirect()->route('admin.services');
    }
}
