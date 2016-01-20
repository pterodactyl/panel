<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
