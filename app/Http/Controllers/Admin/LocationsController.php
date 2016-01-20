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

use DB;
use Alert;

use Pterodactyl\Models;
use Pterodactyl\Repositories\LocationRepository;
use Pterodactyl\Http\Controllers\Controller;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

use Illuminate\Http\Request;

class LocationsController extends Controller
{

    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.locations.index', [
            'locations' => Models\Location::select(
                    'locations.*',
                    DB::raw('(SELECT COUNT(*) FROM nodes WHERE nodes.location = locations.id) as a_nodeCount'),
                    DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.node IN (SELECT nodes.id FROM nodes WHERE nodes.location = locations.id)) as a_serverCount')
                )->paginate(20)
        ]);
    }

    public function deleteLocation(Request $request, $id)
    {
        $model = Models\Location::select(
            'locations.id',
            DB::raw('(SELECT COUNT(*) FROM nodes WHERE nodes.location = locations.id) as a_nodeCount'),
            DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.node IN (SELECT nodes.id FROM nodes WHERE nodes.location = locations.id)) as a_serverCount')
        )->where('id', $id)->first();

        if (!$model) {
            return response()->json([
                'error' => 'No location with that ID exists on the system.'
            ], 404);
        }

        if ($model->a_nodeCount > 0 || $model->a_serverCount > 0) {
            return response()->json([
                'error' => 'You cannot remove a location that is currently assigned to a node or server.'
            ], 422);
        }

        $model->delete();
        return response('', 204);
    }

    public function patchLocation(Request $request, $id)
    {
        try {
            $location = new LocationRepository;
            $location->edit($id, $request->all());
            return response('', 204);
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => 'There was a validation error while processing this request. Location descriptions must be between 1 and 255 characters, and the location code must be between 1 and 10 characters with no spaces or special characters.'
            ], 422);
        } catch (\Exception $ex) {
            // This gets caught and processed into JSON anyways.
            throw $ex;
        }
    }

    public function postLocation(Request $request)
    {
        try {
            $location = new LocationRepository;
            $id = $location->create($request->except([
                '_token'
            ]));
            Alert::success('New location successfully added.')->flash();
            return redirect()->route('admin.locations');
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.locations')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add this location. Please try again.')->flash();
        }
        return redirect()->route('admin.locations')->withInput();
    }

}
