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
use Storage;
use Javascript;
use Illuminate\Http\Request;
use Pterodactyl\Models\Service;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\OptionRepository;
use Pterodactyl\Repositories\VariableRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class OptionController extends Controller
{
    /**
     * Handles request to view page for adding new option.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function new(Request $request)
    {
        $services = Service::with('options')->get();
        Javascript::put(['services' => $services->keyBy('id')]);

        return view('admin.services.options.new', ['services' => $services]);
    }

    /**
     * Handles POST request to create a new option.

     * @param  Request $request
     * @return \Illuminate\Response\RedirectResponse
     */
    public function create(Request $request)
    {
        $repo = new OptionRepository;

        try {
            $option = $repo->create($request->intersect([
                'service_id', 'name', 'description', 'tag',
                'docker_image', 'startup', 'config_from', 'config_startup',
                'config_logs', 'config_files', 'config_stop'
            ]));
            Alert::success('Successfully created new service option.')->flash();

            return redirect()->route('admin.services.option.view', $option->id);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occurred while attempting to create this service. This error has been logged.')->flash();
        }

        return redirect()->route('admin.services.option.new')->withInput();
    }

    /**
     * Display option overview page.
     *
     * @param  Request $request
     * @param  int     $id
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Request $request, $id)
    {
        return view('admin.services.options.view', ['option' => ServiceOption::findOrFail($id)]);
    }

    /**
     * Display variable overview page for a service option.
     *
     * @param  Request $request
     * @param  int     $id
     * @return \Illuminate\View\View
     */
    public function viewVariables(Request $request, $id)
    {
        return view('admin.services.options.variables', ['option' => ServiceOption::with('variables')->findOrFail($id)]);
    }

    /**
     * Handles POST when editing a configration for a service option.
     *
     * @param  Request $request
     * @return \Illuminate\Response\RedirectResponse
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
            Alert::danger('An unhandled exception occurred while attempting to perform that action. This error has been logged.')->flash();
        }

        return redirect()->route('admin.services.option.view', $id);
    }

    /**
     * Handles POST when editing a configration for a service option.
     *
     * @param  Request $request
     * @param  int     $option
     * @param  int     $variable
     * @return \Illuminate\Response\RedirectResponse
     */
    public function editVariable(Request $request, $option, $variable)
    {
        $repo = new VariableRepository;

        try {
            if ($request->input('action') !== 'delete') {
                $variable = $repo->update($variable, $request->only([
                    'name', 'description', 'env_variable',
                    'default_value', 'options', 'rules',
                ]));
                Alert::success("The service variable '{$variable->name}' has been updated.")->flash();
            } else {
                $repo->delete($variable);
                Alert::success("That service variable has been deleted.")->flash();
            }
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option.variables', $option)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception was encountered while attempting to process that request. This error has been logged.')->flash();
        }

        return redirect()->route('admin.services.option.variables', $option);
    }
}
