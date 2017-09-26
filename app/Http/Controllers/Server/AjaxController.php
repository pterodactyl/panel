<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Server;

use Log;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Repositories;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;

class AjaxController extends Controller
{
    /**
     * @var array
     */
    protected $files = [];

    /**
     * @var array
     */
    protected $folders = [];

    /**
     * @var string
     */
    protected $directory;

    /**
     * Resets a database password for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\Http\JsonResponse
     * @deprecated
     */
    public function postResetDatabasePassword(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('reset-db-password', $server);

        $database = Models\Database::where('server_id', $server->id)->findOrFail($request->input('database'));
        $repo = new Repositories\DatabaseRepository;

        try {
            $password = str_random(20);
            $repo->password($database->id, $password);

            return response($password);
        } catch (DisplayException $ex) {
            return response()->json(['error' => $ex->getMessage()], 503);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled error occured while attempting to modify this database\'s password.',
            ], 503);
        }
    }
}
