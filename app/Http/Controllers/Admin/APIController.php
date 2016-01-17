<?php

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
