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

use Javascript;
use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Service\EditOptionScript;
use Pterodactyl\Services\Services\Options\OptionUpdateService;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Services\Services\Options\OptionCreationService;
use Pterodactyl\Services\Services\Options\OptionDeletionService;
use Pterodactyl\Http\Requests\Admin\Service\ServiceOptionFormRequest;
use Pterodactyl\Services\Services\Options\InstallScriptUpdateService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Services\ServiceOption\InvalidCopyFromException;
use Pterodactyl\Exceptions\Services\ServiceOption\HasActiveServersException;
use Pterodactyl\Exceptions\Services\ServiceOption\NoParentConfigurationFoundException;

class OptionController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Services\Options\InstallScriptUpdateService
     */
    protected $installScriptUpdateService;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionCreationService
     */
    protected $optionCreationService;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionDeletionService
     */
    protected $optionDeletionService;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionUpdateService
     */
    protected $optionUpdateService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * OptionController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                    $alert
     * @param \Pterodactyl\Services\Services\Options\InstallScriptUpdateService    $installScriptUpdateService
     * @param \Pterodactyl\Services\Services\Options\OptionCreationService         $optionCreationService
     * @param \Pterodactyl\Services\Services\Options\OptionDeletionService         $optionDeletionService
     * @param \Pterodactyl\Services\Services\Options\OptionUpdateService           $optionUpdateService
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface         $serviceRepository
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface   $serviceOptionRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        InstallScriptUpdateService $installScriptUpdateService,
        OptionCreationService $optionCreationService,
        OptionDeletionService $optionDeletionService,
        OptionUpdateService $optionUpdateService,
        ServiceRepositoryInterface $serviceRepository,
        ServiceOptionRepositoryInterface $serviceOptionRepository
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
     * @param  \Pterodactyl\Http\Requests\Admin\Service\ServiceOptionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ServiceOptionFormRequest $request)
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
     * @param \Pterodactyl\Models\ServiceOption $option
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(ServiceOption $option)
    {
        try {
            $this->optionDeletionService->handle($option->id);
            $this->alert->success()->flash();
        } catch (HasActiveServersException $exception) {
            $this->alert->danger($exception->getMessage())->flash();

            return redirect()->route('admin.services.option.view', $option->id);
        }

        return redirect()->route('admin.services.view', $option->service_id);
    }

    /**
     * Display option overview page.
     *
     * @param  \Pterodactyl\Models\ServiceOption $option
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(ServiceOption $option)
    {
        return view('admin.services.options.view', ['option' => $option]);
    }

    /**
     * Display script management page for an option.
     *
     * @param  int $option
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function viewScripts($option)
    {
        $option = $this->serviceOptionRepository->getWithCopyFrom($option);
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
     * @param  \Illuminate\Http\Request          $request
     * @param  \Pterodactyl\Models\ServiceOption $option
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function editConfiguration(Request $request, ServiceOption $option)
    {
        try {
            $this->optionUpdateService->handle($option, $request->all());
            $this->alert->success(trans('admin/services.options.notices.option_updated'))->flash();
        } catch (NoParentConfigurationFoundException $exception) {
            dd('hodor');
            $this->alert->danger($exception->getMessage())->flash();
        }

        return redirect()->route('admin.services.option.view', $option->id);
    }

    /**
     * Handles POST when updating script for a service option.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\Service\EditOptionScript $request
     * @param  \Pterodactyl\Models\ServiceOption                         $option
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function updateScripts(EditOptionScript $request, ServiceOption $option)
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
