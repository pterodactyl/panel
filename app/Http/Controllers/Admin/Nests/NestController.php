<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Admin\Nests;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Services\Nests\NestUpdateService;
use App\Services\Nests\NestCreationService;
use App\Services\Nests\NestDeletionService;
use App\Contracts\Repository\NestRepositoryInterface;
use App\Http\Requests\Admin\Nest\StoreNestFormRequest;

class NestController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Services\Nests\NestCreationService
     */
    protected $nestCreationService;

    /**
     * @var \App\Services\Nests\NestDeletionService
     */
    protected $nestDeletionService;

    /**
     * @var \App\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Nests\NestUpdateService
     */
    protected $nestUpdateService;

    /**
     * NestController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                         $alert
     * @param \App\Services\Nests\NestCreationService           $nestCreationService
     * @param \App\Services\Nests\NestDeletionService           $nestDeletionService
     * @param \App\Contracts\Repository\NestRepositoryInterface $repository
     * @param \App\Services\Nests\NestUpdateService             $nestUpdateService
     */
    public function __construct(
        AlertsMessageBag $alert,
        NestCreationService $nestCreationService,
        NestDeletionService $nestDeletionService,
        NestRepositoryInterface $repository,
        NestUpdateService $nestUpdateService
    ) {
        $this->alert = $alert;
        $this->nestDeletionService = $nestDeletionService;
        $this->nestCreationService = $nestCreationService;
        $this->nestUpdateService = $nestUpdateService;
        $this->repository = $repository;
    }

    /**
     * Render nest listing page.
     *
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function index(): View
    {
        return view('admin.nests.index', [
            'nests' => $this->repository->getWithCounts(),
        ]);
    }

    /**
     * Render nest creation page.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('admin.nests.new');
    }

    /**
     * Handle the storage of a new nest.
     *
     * @param \App\Http\Requests\Admin\Nest\StoreNestFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     */
    public function store(StoreNestFormRequest $request): RedirectResponse
    {
        $nest = $this->nestCreationService->handle($request->normalize());
        $this->alert->success(trans('admin/nests.notices.created', ['name' => $nest->name]))->flash();

        return redirect()->route('admin.nests.view', $nest->id);
    }

    /**
     * Return details about a nest including all of the eggs and servers per egg.
     *
     * @param int $nest
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function view(int $nest): View
    {
        return view('admin.nests.view', [
            'nest' => $this->repository->getWithEggServers($nest),
        ]);
    }

    /**
     * Handle request to update a nest.
     *
     * @param \App\Http\Requests\Admin\Nest\StoreNestFormRequest $request
     * @param int                                                        $nest
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function update(StoreNestFormRequest $request, int $nest): RedirectResponse
    {
        $this->nestUpdateService->handle($nest, $request->normalize());
        $this->alert->success(trans('admin/nests.notices.updated'))->flash();

        return redirect()->route('admin.nests.view', $nest);
    }

    /**
     * Handle request to delete a nest.
     *
     * @param int $nest
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function destroy(int $nest): RedirectResponse
    {
        $this->nestDeletionService->handle($nest);
        $this->alert->success(trans('admin/nests.notices.deleted'))->flash();

        return redirect()->route('admin.nests');
    }
}
