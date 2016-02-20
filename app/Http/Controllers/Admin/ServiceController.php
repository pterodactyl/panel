<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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

use Alert;
use DB;
use Log;
use Validator;

use Pterodactyl\Models;
use Pterodactyl\Repositories\ServiceRepository;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServiceController extends Controller
{

    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.services.index', [
            'services' => Models\Service::all()
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
                '_token'
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
                )->where('parent_service', $service)->get()
        ]);
    }

    public function postService(Request $request, $service)
    {
        try {
            $repo = new ServiceRepository\Service;
            $repo->update($service, $request->except([
                '_token'
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

    public function getOption(Request $request, $option)
    {
        $opt = Models\ServiceOptions::findOrFail($option);
        return view('admin.services.options.view', [
            'service' => Models\Service::findOrFail($opt->parent_service),
            'option' => $opt,
            'variables' => Models\ServiceVariables::where('option_id', $option)->get(),
            'servers' => Models\Server::select('servers.*', 'users.email as a_ownerEmail')
                ->join('users', 'users.id', '=', 'servers.owner')
                ->where('option', $option)
                ->paginate(10)
        ]);
    }

    public function postOption(Request $request, $option)
    {
        try {
            $repo = new ServiceRepository\Option;
            $repo->update($option, $request->except([
                '_token'
            ]));
            Alert::success('Option settings successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option', $option)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to modify this option.')->flash();
        }
        return redirect()->route('admin.services.option', $option)->withInput();
    }

    public function postOptionVariable(Request $request, $option, $variable)
    {
        if ($variable === 'new') {
            // adding new variable
        } else {
            try {
                $repo = new ServiceRepository\Variable;

                // Because of the way old() works on the display side we prefix all of the variables with thier ID
                // We need to remove that prefix here since the repo doesn't want it.
                $data = [];
                foreach($request->except(['_token']) as $id => $val) {
                    $data[str_replace($variable.'_', '', $id)] = $val;
                }
                $repo->update($variable, $data);
                Alert::success('Successfully updated variable.')->flash();
            } catch (DisplayValidationException $ex) {
                $data = [];
                foreach(json_decode($ex->getMessage(), true) as $id => $val) {
                    $data[$variable.'_'.$id] = $val;
                }
                return redirect()->route('admin.services.option', $option)->withErrors((object) $data)->withInput();
            } catch (DisplayException $ex) {
                Alert::danger($ex->getMessage())->flash();
            } catch (\Exception $ex) {
                Log::error($ex);
                Alert::danger('An error occurred while attempting to update this service.')->flash();
            }
            return redirect()->route('admin.services.option', $option)->withInput();
        }
    }

}
