<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\OptionVariableFormRequest;
use Pterodactyl\Repositories\Eloquent\ServiceVariableRepository;
use Pterodactyl\Services\Services\Variables\VariableUpdateService;
use Pterodactyl\Services\Services\Variables\VariableCreationService;

class VariableController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Services\Variables\VariableCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServiceVariableRepository
     */
    protected $serviceVariableRepository;

    /**
     * @var \Pterodactyl\Services\Services\Variables\VariableUpdateService
     */
    protected $updateService;

    public function __construct(
        AlertsMessageBag $alert,
        EggRepositoryInterface $serviceOptionRepository,
        ServiceVariableRepository $serviceVariableRepository,
        VariableCreationService $creationService,
        VariableUpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->serviceOptionRepository = $serviceOptionRepository;
        $this->serviceVariableRepository = $serviceVariableRepository;
        $this->updateService = $updateService;
    }

    /**
     * Handles POST request to create a new option variable.
     *
     * @param \Pterodactyl\Http\Requests\Admin\OptionVariableFormRequest $request
     * @param \Pterodactyl\Models\Egg                                    $option
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
     */
    public function store(OptionVariableFormRequest $request, Egg $option)
    {
        $this->creationService->handle($option->id, $request->normalize());
        $this->alert->success(trans('admin/services.variables.notices.variable_created'))->flash();

        return redirect()->route('admin.services.option.variables', $option->id);
    }

    /**
     * Display variable overview page for a service option.
     *
     * @param int $option
     * @return \Illuminate\View\View
     */
    public function view($option)
    {
        $option = $this->serviceOptionRepository->getWithVariables($option);

        return view('admin.services.options.variables', ['option' => $option]);
    }

    /**
     * Handles POST when editing a configration for a service variable.
     *
     * @param \Pterodactyl\Http\Requests\Admin\OptionVariableFormRequest $request
     * @param \Pterodactyl\Models\Egg                                    $option
     * @param \Pterodactyl\Models\EggVariable                            $variable
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
     */
    public function update(OptionVariableFormRequest $request, Egg $option, EggVariable $variable)
    {
        $this->updateService->handle($variable, $request->normalize());
        $this->alert->success(trans('admin/services.variables.notices.variable_updated', [
            'variable' => $variable->name,
        ]))->flash();

        return redirect()->route('admin.services.option.variables', $option->id);
    }

    /**
     * Delete a service variable from the system.
     *
     * @param \Pterodactyl\Models\Egg         $option
     * @param \Pterodactyl\Models\EggVariable $variable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Egg $option, EggVariable $variable)
    {
        $this->serviceVariableRepository->delete($variable->id);
        $this->alert->success(trans('admin/services.variables.notices.variable_deleted', [
            'variable' => $variable->name,
        ]))->flash();

        return redirect()->route('admin.services.option.variables', $option->id);
    }
}
