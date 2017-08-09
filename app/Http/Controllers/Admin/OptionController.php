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

use Log;
use Alert;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\OptionVariableFormRequest;
use Pterodactyl\Http\Requests\Admin\ServiceOptionFormRequest;
use Pterodactyl\Services\Services\Options\CreationService;
use Pterodactyl\Services\Services\Variables\VariableCreationService;
use Route;
use Javascript;
use Illuminate\Http\Request;
use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\OptionRepository;
use Pterodactyl\Repositories\VariableRepository;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Http\Requests\Admin\Service\EditOptionScript;
use Pterodactyl\Http\Requests\Admin\Service\StoreOptionVariable;

class OptionController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Services\Options\CreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $serviceRepository;

    /**
     * @var \Pterodactyl\Services\Services\Variables\VariableCreationService
     */
    protected $variableCreationService;

    /**
     * OptionController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                $alert
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface     $serviceRepository
     * @param \Pterodactyl\Services\Services\Options\CreationService           $creationService
     * @param \Pterodactyl\Services\Services\Variables\VariableCreationService $variableCreationService
     */
    public function __construct(
        AlertsMessageBag $alert,
        ServiceRepositoryInterface $serviceRepository,
        CreationService $creationService,
        VariableCreationService $variableCreationService
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->serviceRepository = $serviceRepository;
        $this->variableCreationService = $variableCreationService;
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
     * @param  \Pterodactyl\Http\Requests\Admin\ServiceOptionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ServiceOptionFormRequest $request)
    {
        $option = $this->creationService->handle($request->normalize());
        $this->alert->success(trans('admin/services.options.notices.option_created'))->flash();

        return redirect()->route('admin.services.option.view', $option->id);
    }

    /**
     * Handles POST request to create a new option variable.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\OptionVariableFormRequest $request
     * @param  \Pterodactyl\Models\ServiceOption                          $option
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createVariable(OptionVariableFormRequest $request, ServiceOption $option)
    {
        $this->variableCreationService->handle($option->id, $request->normalize());
        $this->alert->success(trans('admin/services.variables.notices.variable_created'))->flash();

        return redirect()->route('admin.services.option.variables', $option->id);
    }

    /**
     * Display option overview page.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Request $request, $id)
    {
        return view('admin.services.options.view', ['option' => ServiceOption::findOrFail($id)]);
    }

    /**
     * Display variable overview page for a service option.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewVariables(Request $request, $id)
    {
        return view('admin.services.options.variables', ['option' => ServiceOption::with('variables')
            ->findOrFail($id), ]);
    }

    /**
     * Display script management page for an option.
     *
     * @param  Request $request
     * @param  int     $id
     * @return \Illuminate\View\View
     */
    public function viewScripts(Request $request, $id)
    {
        $option = ServiceOption::with('copyFrom')->findOrFail($id);

        return view('admin.services.options.scripts', [
            'copyFromOptions' => ServiceOption::whereNull('copy_script_from')->where([
                ['service_id', $option->service_id],
                ['id', '!=', $option->id],
            ])->get(),
            'relyOnScript' => ServiceOption::where('copy_script_from', $option->id)->get(),
            'option' => $option,
        ]);
    }

    /**
     * Handles POST when editing a configration for a service option.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editConfiguration(Request $request, $id)
    {
        $repo = new OptionRepository;

        try {
            if ($request->input('action') !== 'delete') {
                $repo->update($id, $request->intersect([
                    'name', 'description', 'tag', 'docker_image', 'startup',
                    'config_from', 'config_stop', 'config_logs', 'config_files', 'config_startup',
                ]));
                Alert::success('Service option configuration has been successfully updated.')->flash();
            } else {
                $option = ServiceOption::with('service')->where('id', $id)->first();
                $repo->delete($id);
                Alert::success('Successfully deleted service option from the system.')->flash();

                return redirect()->route('admin.services.view', $option->service_id);
            }
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option.view', $id)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occurred while attempting to perform that action. This error has been logged.')
                ->flash();
        }

        return redirect()->route('admin.services.option.view', $id);
    }

    /**
     * Handles POST when editing a configration for a service option.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\Service\StoreOptionVariable $request
     * @param  int                                                          $option
     * @param  int                                                          $variable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editVariable(StoreOptionVariable $request, $option, $variable)
    {
        $repo = new VariableRepository;

        try {
            if ($request->input('action') !== 'delete') {
                $variable = $repo->update($variable, $request->normalize());
                Alert::success("The service variable '{$variable->name}' has been updated.")->flash();
            } else {
                $repo->delete($variable);
                Alert::success('That service variable has been deleted.')->flash();
            }
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception was encountered while attempting to process that request. This error has been logged.')
                ->flash();
        }

        return redirect()->route('admin.services.option.variables', $option);
    }

    /**
     * Handles POST when updating script for a service option.
     *
     * @param \Pterodactyl\Http\Requests\Admin\Service\EditOptionScript $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateScripts(EditOptionScript $request)
    {
        try {
            $this->repository->scripts($request->normalize());

            Alert::success('Successfully updated option scripts to be run when servers are installed.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception was encountered while attempting to process that request. This error has been logged.')
                ->flash();
        }

        return redirect()->route('admin.services.option.scripts', $id);
    }
}
