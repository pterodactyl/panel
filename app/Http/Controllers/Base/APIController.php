<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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

namespace Pterodactyl\Http\Controllers\Base;

use Log;
use Alert;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Repositories\APIRepository;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayValidationException;

class APIController extends Controller
{
    public function index(Request $request)
    {
        return view('base.api.index', [
            'keys' => Models\APIKey::where('user_id', $request->user()->id)->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('base.api.new');
    }

    public function save(Request $request)
    {
        try {
            $repo = new APIRepository($request->user());
            $secret = $repo->create($request->intersect([
                'memo', 'allowed_ips',
                'adminPermissions', 'permissions',
            ]));
            Alert::success('An API Key-Pair has successfully been generated. The API secret for this public key is shown below and will not be shown again.<br /><br /><code>' . $secret . '</code>')->flash();

            return redirect()->route('account.api');
        } catch (DisplayValidationException $ex) {
            return redirect()->route('account.api.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add this API key.')->flash();
        }

        return redirect()->route('account.api.new')->withInput();
    }

    public function revoke(Request $request, $key)
    {
        try {
            $repo = new APIRepository($request->user());
            $repo->revoke($key);

            return response('', 204);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An error occured while attempting to remove this key.',
            ], 503);
        }
    }
}
