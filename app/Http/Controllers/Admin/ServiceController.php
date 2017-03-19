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
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServiceRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServiceController extends Controller
{
    /**
     * Display service overview page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.services.index', [
            'services' => Models\Service::withCount('servers', 'options', 'packs')->get(),
        ]);
    }

    /**
     * Display create service page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function new(Request $request)
    {
        return view('admin.services.new');
    }

    /**
     * Return base view for a service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function view(Request $request, $id)
    {
        return view('admin.services.view', [
            'service' => Models\Service::with('options', 'options.servers')->findOrFail($id),
        ]);
    }

    /**
     * Return function editing view for a service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewFunctions(Request $request, $id)
    {
        return view('admin.services.functions', ['service' => Models\Service::findOrFail($id)]);
    }

    /**
     * Handle post action for new service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $repo = new ServiceRepository;

        try {
            $service = $repo->create($request->intersect([
                'name', 'description', 'folder', 'startup',
            ]));
            Alert::success('Successfully created new service!')->flash();

            return redirect()->route('admin.services.view', $service->id);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to add a new service. This error has been logged.')->flash();
        }

        return redirect()->route('admin.services.new')->withInput();
    }

    /**
     * Edits configuration for a specific service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit(Request $request, $id)
    {
        $repo = new ServiceRepository;
        $redirectTo = ($request->input('redirect_to')) ? 'admin.services.view.functions' : 'admin.services.view';

        try {
            if ($request->input('action') !== 'delete') {
                $repo->update($id, $request->intersect([
                    'name', 'description', 'folder', 'startup', 'index_file',
                ]));
                Alert::success('Service has been updated successfully.')->flash();
            } else {
                $repo->delete($id);
                Alert::success('Successfully deleted service from the system.')->flash();

                return redirect()->route('admin.services');
            }
        } catch (DisplayValidationException $ex) {
            return redirect()->route($redirectTo, $id)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occurred while attempting to update this service. This error has been logged.')->flash();
        }

        return redirect()->route($redirectTo, $id);
    }
}
