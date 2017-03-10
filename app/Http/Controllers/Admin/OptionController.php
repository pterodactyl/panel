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
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\OptionRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class OptionController extends Controller
{
    /**
     * Display option overview page.
     *
     * @param  Request $request
     * @param  int     $id
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Request $request, $id)
    {
        return view('admin.services.options.view', ['option' => Models\ServiceOption::findOrFail($id)]);
    }

    public function editConfiguration(Request $request, $id)
    {
        $repo = new OptionRepository;

        try {
            $repo->update($id, $request->intersect([
                'name', 'description', 'tag', 'docker_image', 'startup',
                'config_from', 'config_stop', 'config_logs', 'config_files', 'config_startup',
            ]));

            Alert::success('Service option configuration has been successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.services.option.view', $id)->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occurred while attempting to update this service option. This error has been logged.')->flash();
        }

        return redirect()->route('admin.services.option.view', $id);
    }
}
