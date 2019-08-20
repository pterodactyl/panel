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
use App\Models\Egg;
use App\Models\EggVariable;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Http\Controllers\Controller;
use App\Contracts\Repository\EggRepositoryInterface;
use App\Services\Eggs\Variables\VariableUpdateService;
use App\Http\Requests\Admin\Egg\EggVariableFormRequest;
use App\Services\Eggs\Variables\VariableCreationService;
use App\Contracts\Repository\EggVariableRepositoryInterface;

class EggVariableController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \App\Services\Eggs\Variables\VariableCreationService
     */
    protected $creationService;

    /**
     * @var \App\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * @var \App\Services\Eggs\Variables\VariableUpdateService
     */
    protected $updateService;

    /**
     * @var \App\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $variableRepository;

    /**
     * EggVariableController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                $alert
     * @param \App\Services\Eggs\Variables\VariableCreationService     $creationService
     * @param \App\Services\Eggs\Variables\VariableUpdateService       $updateService
     * @param \App\Contracts\Repository\EggRepositoryInterface         $repository
     * @param \App\Contracts\Repository\EggVariableRepositoryInterface $variableRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        VariableCreationService $creationService,
        VariableUpdateService $updateService,
        EggRepositoryInterface $repository,
        EggVariableRepositoryInterface $variableRepository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->repository = $repository;
        $this->updateService = $updateService;
        $this->variableRepository = $variableRepository;
    }

    /**
     * Handle request to view the variables attached to an Egg.
     *
     * @param int $egg
     * @return \Illuminate\View\View
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function view(int $egg): View
    {
        $egg = $this->repository->getWithVariables($egg);

        return view('admin.eggs.variables', ['egg' => $egg]);
    }

    /**
     * Handle a request to create a new Egg variable.
     *
     * @param \App\Http\Requests\Admin\Egg\EggVariableFormRequest $request
     * @param \App\Models\Egg $egg
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \App\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function store(EggVariableFormRequest $request, Egg $egg): RedirectResponse
    {
        $this->creationService->handle($egg->id, $request->normalize());
        $this->alert->success(trans('admin/nests.variables.notices.variable_created'))->flash();

        return redirect()->route('admin.nests.egg.variables', $egg->id);
    }

    /**
     * Handle a request to update an existing Egg variable.
     *
     * @param \App\Http\Requests\Admin\Egg\EggVariableFormRequest $request
     * @param \App\Models\Egg                                     $egg
     * @param \App\Models\EggVariable                             $variable
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function update(EggVariableFormRequest $request, Egg $egg, EggVariable $variable): RedirectResponse
    {
        $this->updateService->handle($variable, $request->normalize());
        $this->alert->success(trans('admin/nests.variables.notices.variable_updated', [
            'variable' => $variable->name,
        ]))->flash();

        return redirect()->route('admin.nests.egg.variables', $egg->id);
    }

    /**
     * Handle a request to delete an existing Egg variable from the Panel.
     *
     * @param int                             $egg
     * @param \App\Models\EggVariable $variable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $egg, EggVariable $variable): RedirectResponse
    {
        $this->variableRepository->delete($variable->id);
        $this->alert->success(trans('admin/nests.variables.notices.variable_deleted', [
            'variable' => $variable->name,
        ]))->flash();

        return redirect()->route('admin.nests.egg.variables', $egg);
    }
}
