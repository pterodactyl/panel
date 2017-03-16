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
use Illuminate\Http\Request;
use Pterodactyl\Models\Location;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\LocationRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class LocationController extends Controller
{
    /**
     * Return the location overview page.
     *
     * @param  Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.locations.index', [
            'locations' => Location::withCount('nodes', 'servers')->get(),
        ]);
    }

    /**
     * Return the location view page.
     *
     * @param  Request $request
     * @param  int     $id
     * @return \Illuminate\View\View
     */
    public function view(Request $request, $id)
    {
        return view('admin.locations.view', ['location' => Location::with('nodes.servers')->findOrFail($id)]);
    }

    /**
     * Handle request to create new location.
     *
     * @param  Request $request
     * @return \Illuminate\Response\RedirectResponse
     */
    public function create(Request $request)
    {
        $repo = new LocationRepository;

        try {
            $location = $repo->create($request->intersect(['short', 'long']));
            Alert::success('Location was created successfully.')->flash();

            return redirect()->route('admin.locations.view', $location->id);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.locations')->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::error('An unhandled exception occurred while processing this request. This error has been logged.')->flash();
        }

        return redirect()->route('admin.locations');
    }

    /**
     * Handle request to update or delete location.
     *
     * @param  Request $request
     * @param  int     $id
     * @return \Illuminate\Response\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $repo = new LocationRepository;

        try {
            if ($request->input('action') !== 'delete') {
                $location = $repo->update($id, $request->intersect(['short', 'long']));
                Alert::success('Location was updated successfully.')->flash();
            } else {
                $repo->delete($id);

                return redirect()->route('admin.locations');
            }
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.locations.view', $id)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::error('An unhandled exception occurred while processing this request. This error has been logged.')->flash();
        }

        return redirect()->route('admin.locations.view', $id);
    }
}
