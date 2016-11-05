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

use Pterodactyl\Models;
use Pterodactyl\Repositories\DatabaseRepository;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.databases.index', [
            'databases' => Models\Database::select(
                    'databases.*',
                    'database_servers.host as a_host',
                    'database_servers.port as a_port',
                    'servers.id as a_serverId',
                    'servers.name as a_serverName'
                )->join('database_servers', 'database_servers.id', '=', 'databases.db_server')
                ->join('servers', 'databases.server_id', '=', 'servers.id')
                ->paginate(20),
            'dbh' => Models\DatabaseServer::select(
                    'database_servers.*',
                    'nodes.name as a_linkedNode',
                    DB::raw('(SELECT COUNT(*) FROM `databases` WHERE `databases`.`db_server` = database_servers.id) as c_databases')
                )->leftJoin('nodes', 'nodes.id', '=', 'database_servers.linked_node')
                ->paginate(20)
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.databases.new', [
            'nodes' => Models\Node::select('nodes.id', 'nodes.name', 'locations.long as a_location')
                ->join('locations', 'locations.id', '=', 'nodes.location')
                ->get()
        ]);
    }

    public function postNew(Request $request)
    {
        try {
            $repo = new DatabaseRepository;
            $repo->add($request->except([
                '_token'
            ]));

            Alert::success('Successfully added a new database server to the system.')->flash();
            return redirect()->route('admin.databases', [
                'tab' => 'tab_dbservers'
            ]);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.databases.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            if ($ex instanceof DisplayException || $ex instanceof \PDOException) {
                Alert::danger($ex->getMessage())->flash();
            } else {
                Log::error($ex);
                Alert::danger('An error occurred while attempting to delete this database server from the system.')->flash();
            }
            return redirect()->route('admin.databases.new')->withInput();
        }
    }

    public function deleteDatabase(Request $request, $id)
    {
        try {
            $repo = new DatabaseRepository;
            $repo->drop($id);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'error' => ($ex instanceof DisplayException) ? $ex->getMessage() : 'An error occurred while attempting to delete this database from the system.'
            ], 500);
        }
    }

    public function deleteServer(Request $request, $id)
    {
        try {
            $repo = new DatabaseRepository;
            $repo->delete($id);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'error' => ($ex instanceof DisplayException) ? $ex->getMessage() : 'An error occurred while attempting to delete this database server from the system.'
            ], 500);
        }
    }

}
