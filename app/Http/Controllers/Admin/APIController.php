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

use Alert;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Repositories\APIRepository;
use Pterodactyl\Http\Controllers\Controller;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

use Illuminate\Http\Request;

class APIController extends Controller
{

    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        $keys = Models\APIKey::all();
        foreach($keys as &$key) {
            $key->permissions = Models\APIPermission::where('key_id', $key->id)->get();
        }

        return view('admin.api.index', [
            'keys' => $keys
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.api.new');
    }

    public function postNew(Request $request)
    {
        try {
            $api = new APIRepository;
            $secret = $api->new($request->except(['_token']));
            Alert::info('An API Keypair has successfully been generated. The API secret for this public key is shown below and will not be shown again.<br /><br />Secret: <code>' . $secret . '</code>')->flash();
            return redirect()->route('admin.api');
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.api.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add this API key.')->flash();
        }
        return redirect()->route('admin.api.new')->withInput();
    }

    public function deleteRevokeKey(Request $request, $key)
    {
        try {
            $api = new APIRepository;
            $api->revoke($key);
            return response('', 204);
        } catch (\Exception $ex) {
            return response()->json([
                'error' => 'An error occured while attempting to remove this key.'
            ], 503);
        }
    }

}
