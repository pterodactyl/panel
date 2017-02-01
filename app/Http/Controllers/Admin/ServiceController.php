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

use DB;
use Log;
use Alert;
use Storage;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServiceRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServiceController extends Controller
{
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.services.index', [
            'services' => Models\Service::select(
                    'services.*',
                    DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.service = services.id) as c_servers')
                )->get(),
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.services.new');
    }

    public function postNew(Request $request)
    {
        try {
            $repo = new ServiceRepository\Service;
            $id = $repo->create($request->except([
                '_token',
            ]));
            Alert::success('Successfully created new service!')->flash();

            return redirect()->route('admin.services.service', $id);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new service.')->flash();
        }

        return redirect()->route('admin.services.new')->withInput();
    }

    public function getService(Request $request, $service)
    {
        return view('admin.services.view', [
            'service' => Models\Service::findOrFail($service),
            'options' => Models\ServiceOptions::select(
                    'service_options.*',
                    DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.option = service_options.id) as c_servers')
                )->where('parent_service', $service)->get(),
        ]);
    }

    public function postService(Request $request, $service)
    {
        try {
            $repo = new ServiceRepository\Service;
            $repo->update($service, $request->except([
                '_token',
            ]));
            Alert::success('Successfully updated this service.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.service', $service)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occurred while attempting to update this service.')->flash();
        }

        return redirect()->route('admin.services.service', $service)->withInput();
    }

    public function deleteService(Request $request, $service)
    {
        try {
            $repo = new ServiceRepository\Service;
            $repo->delete($service);
            Alert::success('Successfully deleted that service.')->flash();

            return redirect()->route('admin.services');
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error was encountered while attempting to delete that service.')->flash();
        }

        return redirect()->route('admin.services.service', $service);
    }

    public function getOption(Request $request, $service, $option)
    {
        $opt = Models\ServiceOptions::findOrFail($option);

        return view('admin.services.options.view', [
            'service' => Models\Service::findOrFail($opt->parent_service),
            'option' => $opt,
            'variables' => Models\ServiceVariables::where('option_id', $option)->get(),
            'servers' => Models\Server::select('servers.*', 'users.email as a_ownerEmail')
                ->join('users', 'users.id', '=', 'servers.owner')
                ->where('option', $option)
                ->paginate(10),
        ]);
    }

    public function postOption(Request $request, $service, $option)
    {
        try {
            $repo = new ServiceRepository\Option;
            $repo->update($option, $request->except([
                '_token',
            ]));
            Alert::success('Option settings successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option', [$service, $option])->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to modify this option.')->flash();
        }

        return redirect()->route('admin.services.option', [$service, $option])->withInput();
    }

    public function deleteOption(Request $request, $service, $option)
    {
        try {
            $service = Models\ServiceOptions::select('parent_service')->where('id', $option)->first();
            $repo = new ServiceRepository\Option;
            $repo->delete($option);

            Alert::success('Successfully deleted that option.')->flash();

            return redirect()->route('admin.services.service', $service->parent_service);
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error was encountered while attempting to delete this option.')->flash();
        }

        return redirect()->route('admin.services.option', [$service, $option]);
    }

    public function postOptionVariable(Request $request, $service, $option, $variable)
    {
        try {
            $repo = new ServiceRepository\Variable;

            // Because of the way old() works on the display side we prefix all of the variables with thier ID
            // We need to remove that prefix here since the repo doesn't want it.
            $data = [
                'user_viewable' => '0',
                'user_editable' => '0',
                'required' => '0',
            ];
            foreach ($request->except(['_token']) as $id => $val) {
                $data[str_replace($variable . '_', '', $id)] = $val;
            }
            $repo->update($variable, $data);
            Alert::success('Successfully updated variable.')->flash();
        } catch (DisplayValidationException $ex) {
            $data = [];
            foreach (json_decode($ex->getMessage(), true) as $id => $val) {
                $data[$variable . '_' . $id] = $val;
            }

            return redirect()->route('admin.services.option', [$service, $option])->withErrors((object) $data)->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occurred while attempting to update this service.')->flash();
        }

        return redirect()->route('admin.services.option', [$service, $option])->withInput();
    }

    public function getNewVariable(Request $request, $service, $option)
    {
        return view('admin.services.options.variable', [
            'service' => Models\Service::findOrFail($service),
            'option' => Models\ServiceOptions::where('parent_service', $service)->where('id', $option)->firstOrFail(),
        ]);
    }

    public function postNewVariable(Request $request, $service, $option)
    {
        try {
            $repo = new ServiceRepository\Variable;
            $repo->create($option, $request->except([
                '_token',
            ]));
            Alert::success('Successfully added new variable to this option.')->flash();

            return redirect()->route('admin.services.option', [$service, $option]);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option.variable.new', [$service, $option])->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occurred while attempting to add this variable.')->flash();
        }

        return redirect()->route('admin.services.option.variable.new', [$service, $option])->withInput();
    }

    public function newOption(Request $request, $service)
    {
        return view('admin.services.options.new', [
            'service' => Models\Service::findOrFail($service),
        ]);
    }

    public function postNewOption(Request $request, $service)
    {
        try {
            $repo = new ServiceRepository\Option;
            $id = $repo->create($service, $request->except([
                '_token',
            ]));
            Alert::success('Successfully created new service option.')->flash();

            return redirect()->route('admin.services.option', [$service, $id]);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option.new', $service)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add this service option.')->flash();
        }

        return redirect()->route('admin.services.option.new', $service)->withInput();
    }

    public function deleteVariable(Request $request, $service, $option, $variable)
    {
        try {
            $repo = new ServiceRepository\Variable;
            $repo->delete($variable);
            Alert::success('Deleted variable.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to delete that variable.')->flash();
        }

        return redirect()->route('admin.services.option', [$service, $option]);
    }

    public function getConfiguration(Request $request, $serviceId)
    {
        $service = Models\Service::findOrFail($serviceId);

        return view('admin.services.config', [
            'service' => $service,
            'contents' => [
                'json' => Storage::get('services/' . $service->file . '/main.json'),
                'index' => Storage::get('services/' . $service->file . '/index.js'),
            ],
        ]);
    }

    public function postConfiguration(Request $request, $serviceId)
    {
        try {
            $repo = new ServiceRepository\Service;
            $repo->updateFile($serviceId, $request->except([
                '_token',
            ]));

            return response('', 204);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 503);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An error occured while attempting to save the file.',
            ], 503);
        }
    }
}
