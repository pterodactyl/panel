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

use Pterodactyl\Models;

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
                ->join('servers', 'databases.server', '=', 'servers.id')
                ->paginate(20),
            'dbh' => Models\DatabaseServer::select(
                    'database_servers.*',
                    'nodes.name as a_linkedNode',
                    DB::raw('(SELECT COUNT(*) FROM `databases` WHERE `databases`.`db_server` = database_servers.id) as c_databases')
                )->join('nodes', 'nodes.id', '=', 'database_servers.linked_node')
                ->paginate(20)
        ]);
    }

}
