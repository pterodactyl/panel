<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers\Admin\Nests;

use Javascript;
use Illuminate\View\View;
use App\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Http\Controllers\Controller;
use App\Services\Eggs\EggUpdateService;
use App\Services\Eggs\EggCreationService;
use App\Services\Eggs\EggDeletionService;
use App\Http\Requests\Admin\Egg\EggFormRequest;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Contracts\Repository\NestRepositoryInterface;

class EggController extends Controller
{
    protected $alert;
    protected $creationService;
    protected $deletionService;
    protected $nestRepository;
    protected $repository;
    protected $updateService;

    public function __construct(
        AlertsMessageBag $alert,
        EggCreationService $creationService,
        EggDeletionService $deletionService,
        EggRepositoryInterface $repository,
        EggUpdateService $updateService,
        NestRepositoryInterface $nestRepository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->nestRepository = $nestRepository;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Handle a request to display the Egg creation page.
     *
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function create(): View
    {
        $nests = $this->nestRepository->getWithEggs();
        Javascript::put(['nests' => $nests->keyBy('id')]);

        return view('admin.eggs.new', ['nests' => $nests]);
    }

    /**
     * Handle request to store a new Egg.
     *
     * @param \App\Http\Requests\Admin\Egg\EggFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function store(EggFormRequest $request): RedirectResponse
    {
        $egg = $this->creationService->handle($request->normalize());
        $this->alert->success(trans('admin/nests.eggs.notices.egg_created'))->flash();

        return redirect()->route('admin.nests.egg.view', $egg->id);
    }

    /**
     * Handle request to view a single Egg.
     *
     * @param \App\Models\Egg $egg
     * @return \Illuminate\View\View
     */
    public function view(Egg $egg): View
    {
        return view('admin.eggs.view', ['egg' => $egg]);
    }

    /**
     * Handle request to update an Egg.
     *
     * @param \App\Http\Requests\Admin\Egg\EggFormRequest $request
     * @param \App\Models\Egg                             $egg
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function update(EggFormRequest $request, Egg $egg): RedirectResponse
    {
        $this->updateService->handle($egg, $request->normalize());
        $this->alert->success(trans('admin/nests.eggs.notices.updated'))->flash();

        return redirect()->route('admin.nests.egg.view', $egg->id);
    }

    /**
     * Handle request to destroy an egg.
     *
     * @param \App\Models\Egg $egg
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Service\Egg\HasChildrenException
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Egg $egg): RedirectResponse
    {
        $this->deletionService->handle($egg->id);
        $this->alert->success(trans('admin/nests.eggs.notices.deleted'))->flash();

        return redirect()->route('admin.nests.view', $egg->nest_id);
    }
}
