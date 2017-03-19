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
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\DatabaseRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class DatabaseController extends Controller
{
    /**
     * Display database host index.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.databases.index', [
            'locations' => Location::with('nodes')->get(),
            'hosts' => DatabaseHost::withCount('databases')->with('node')->get(),
        ]);
    }

    /**
     * Display database host to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function view(Request $request, $id)
    {
        return view('admin.databases.view', [
            'locations' => Location::with('nodes')->get(),
            'host' => DatabaseHost::with('databases.server')->findOrFail($id),
        ]);
    }

    /**
     * Handle post request to create database host.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $repo = new DatabaseRepository;

        try {
            $host = $repo->add($request->intersect([
                'name', 'username', 'password',
                'host', 'port', 'node_id',
            ]));
            Alert::success('Successfully created new database host on the system.')->flash();

            return redirect()->route('admin.databases.view', $host->id);
        } catch (\PDOException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.databases')->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error was encountered while trying to process this request. This error has been logged.')->flash();
        }

        return redirect()->route('admin.databases');
    }

    /**
     * Handle post request to update a database host.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $repo = new DatabaseRepository;

        try {
            if ($request->input('action') !== 'delete') {
                $host = $repo->update($id, $request->intersect([
                    'name', 'username', 'password',
                    'host', 'port', 'node_id',
                ]));
                Alert::success('Database host was updated successfully.')->flash();
            } else {
                $repo->delete($id);

                return redirect()->route('admin.databases');
            }
        } catch (\PDOException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.databases.view', $id)->withErrors(json_decode($ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error was encountered while trying to process this request. This error has been logged.')->flash();
        }

        return redirect()->route('admin.databases.view', $id);
    }
}
