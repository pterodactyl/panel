<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Javascript;
use Pterodactyl\Models\Egg;
use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Service\EggFormRequest;
use Pterodactyl\Services\Services\Options\EggUpdateService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Service\EditOptionScript;
use Pterodactyl\Services\Services\Options\EggCreationService;
use Pterodactyl\Services\Services\Options\EggDeletionService;
use Pterodactyl\Services\Services\Options\InstallScriptService;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;
use Pterodactyl\Exceptions\Service\Egg\NoParentConfigurationFoundException;

class OptionController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Services\Options\InstallScriptService
     */
    protected $installScriptUpdateService;

    /**
     * @var \Pterodactyl\Services\Services\Options\EggCreationService
     */
    protected $optionCreationService;

    /**
     * @var \Pterodactyl\Services\Services\Options\EggDeletionService
     */
    protected $optionDeletionService;

    /**
     * @var \Pterodactyl\Services\Services\Options\EggUpdateService
     */
    protected $optionUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * OptionController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                           $alert
     * @param \Pterodactyl\Services\Services\Options\InstallScriptService $installScriptUpdateService
     * @param \Pterodactyl\Services\Services\Options\EggCreationService   $optionCreationService
     * @param \Pterodactyl\Services\Services\Options\EggDeletionService   $optionDeletionService
     * @param \Pterodactyl\Services\Services\Options\EggUpdateService     $optionUpdateService
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface   $serviceRepository
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface    $serviceOptionRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        InstallScriptService $installScriptUpdateService,
        EggCreationService $optionCreationService,
        EggDeletionService $optionDeletionService,
        EggUpdateService $optionUpdateService,
        NestRepositoryInterface $serviceRepository,
        EggRepositoryInterface $serviceOptionRepository
    ) {
        $this->alert = $alert;
        $this->installScriptUpdateService = $installScriptUpdateService;
        $this->optionCreationService = $optionCreationService;
        $this->optionDeletionService = $optionDeletionService;
        $this->optionUpdateService = $optionUpdateService;
        $this->serviceRepository = $serviceRepository;
        $this->serviceOptionRepository = $serviceOptionRepository;
    }

    /**
     * Handles request to view page for adding new option.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $services = $this->serviceRepository->getWithOptions();
        Javascript::put(['services' => $services->keyBy('id')]);

        return view('admin.services.options.new', ['services' => $services]);
    }

    /**
     * Handle adding a new service option.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\EggFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(EggFormRequest $request)
    {
        try {
            $option = $this->optionCreationService->handle($request->normalize());
            $this->alert->success(trans('admin/services.options.notices.option_created'))->flash();
        } catch (NoParentConfigurationFoundException $exception) {
            $this->alert->danger($exception->getMessage())->flash();

            return redirect()->back()->withInput();
        }

        return redirect()->route('admin.services.option.view', $option->id);
    }

    /**
     * Delete a given option from the database.
     *
     * @param \Pterodactyl\Models\Egg $option
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Egg $option)
    {
        $this->optionDeletionService->handle($option->id);
        $this->alert->success(trans('admin/services.options.notices.option_deleted'))->flash();

        return redirect()->route('admin.services.view', $option->service_id);
    }

    /**
     * Display option overview page.
     *
     * @param \Pterodactyl\Models\Egg $option
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Egg $option)
    {
        return view('admin.services.options.view', ['option' => $option]);
    }

    /**
     * Display script management page for an option.
     *
     * @param int $option
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function viewScripts($option)
    {
        $option = $this->serviceOptionRepository->getWithCopyAttributes($option);
        $copyOptions = $this->serviceOptionRepository->findWhere([
            ['copy_script_from', '=', null],
            ['service_id', '=', $option->service_id],
            ['id', '!=', $option],
        ]);
        $relyScript = $this->serviceOptionRepository->findWhere([['copy_script_from', '=', $option]]);

        return view('admin.services.options.scripts', [
            'copyFromOptions' => $copyOptions,
            'relyOnScript' => $relyScript,
            'option' => $option,
        ]);
    }

    /**
     * Handles POST when editing a configration for a service option.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Egg  $option
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function editConfiguration(Request $request, Egg $option)
    {
        try {
            $this->optionUpdateService->handle($option, $request->all());
            $this->alert->success(trans('admin/services.options.notices.option_updated'))->flash();
        } catch (NoParentConfigurationFoundException $exception) {
            $this->alert->danger($exception->getMessage())->flash();
        }

        return redirect()->route('admin.services.option.view', $option->id);
    }

    /**
     * Handles POST when updating script for a service option.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\EditOptionScript $request
     * @param \Pterodactyl\Models\Egg                                   $option
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateScripts(EditOptionScript $request, Egg $option)
    {
        try {
            $this->installScriptUpdateService->handle($option, $request->normalize());
            $this->alert->success(trans('admin/services.options.notices.script_updated'))->flash();
        } catch (InvalidCopyFromException $exception) {
            $this->alert->danger($exception->getMessage())->flash();
        }

        return redirect()->route('admin.services.option.scripts', $option->id);
    }
}
