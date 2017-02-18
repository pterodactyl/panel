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

namespace Pterodactyl\Http\Controllers\Server;

use Log;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Repositories;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayValidationException;

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
     * Controller Constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Returns true or false depending on the power status of the requested server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string $uuid
     * @return \Illuminate\Contracts\View\View
     */
    public function getStatus(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);

        if (! $server) {
            return response()->json([], 404);
        }

        if (! $server->installed) {
            return response()->json(['status' => 20]);
        }

        if ($server->suspended) {
            return response()->json(['status' => 30]);
        }

        try {
            $res = $server->guzzleClient()->request('GET', '/server');
            if ($res->getStatusCode() === 200) {
                return response()->json(json_decode($res->getBody()));
            }
        } catch (RequestException $e) {
            //
        }

        return response()->json([]);
    }

    /**
     * Returns a listing of files in a given directory for a server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string $uuid`
     * @return \Illuminate\Contracts\View\View
     */
    public function postDirectoryList(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('list-files', $server);

        $this->directory = '/' . trim(urldecode($request->input('directory', '/')), '/');
        $prevDir = [
            'header' => ($this->directory !== '/') ? $this->directory : '',
        ];
        if ($this->directory !== '/') {
            $prevDir['first'] = true;
        }

        // Determine if we should show back links in the file browser.
        // This code is strange, and could probably be rewritten much better.
        $goBack = explode('/', trim($this->directory, '/'));
        if (! empty(array_filter($goBack)) && count($goBack) >= 2) {
            $prevDir['show'] = true;
            array_pop($goBack);
            $prevDir['link'] = '/' . implode('/', $goBack);
            $prevDir['link_show'] = implode('/', $goBack) . '/';
        }

        $controller = new Repositories\Daemon\FileRepository($uuid);

        try {
            $directoryContents = $controller->returnDirectoryListing($this->directory);
        } catch (DisplayException $ex) {
            return response($ex->getMessage(), 500);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response('An error occured while attempting to load the requested directory, please try again.', 500);
        }

        return view('server.files.list', [
            'server' => $server,
            'files' => $directoryContents->files,
            'folders' => $directoryContents->folders,
            'editableMime' => Repositories\HelperRepository::editableFiles(),
            'directory' => $prevDir,
        ]);
    }

    /**
     * Handles a POST request to save a file.
     *
     * @param  Request $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function postSaveFile(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('save-files', $server);

        $controller = new Repositories\Daemon\FileRepository($uuid);

        try {
            $controller->saveFileContents($request->input('file'), $request->input('contents'));

            return response(null, 204);
        } catch (DisplayException $ex) {
            return response($ex->getMessage(), 500);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response('An error occured while attempting to save this file, please try again.', 500);
        }
    }

    /**
     * [postSetPrimary description].
     * @param  Request $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function postSetPrimary(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid)->load('allocations');
        $this->authorize('set-connection', $server);

        if ((int) $request->input('allocation') === $server->allocation_id) {
            return response()->json([
                'error' => 'You are already using this as your default connection.',
            ], 409);
        }

        try {
            $allocation = $server->allocations->where('id', $request->input('allocation'))->where('server_id', $server->id)->first();
            if (! $allocation) {
                return response()->json([
                    'error' => 'No allocation matching your request was found in the system.',
                ], 422);
            }

            $repo = new Repositories\ServerRepository;
            $repo->changeBuild($server->id, [
                'default' => $allocation->ip . ':' . $allocation->port,
            ]);

            return response('The default connection for this server has been updated. Please be aware that you will need to restart your server for this change to go into effect.');
        } catch (DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage(), true),
            ], 422);
        } catch (DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 503);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled exception occured while attemping to modify the default connection for this server.',
            ], 503);
        }
    }

    public function postResetDatabasePassword(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('reset-db-password', $server);

        $database = Models\Database::where('id', $request->input('database'))->where('server_id', $server->id)->firstOrFail();
        try {
            $repo = new Repositories\DatabaseRepository;
            $password = str_random(16);
            $repo->modifyPassword($request->input('database'), $password);

            return response($password);
        } catch (\Pterodactyl\Exceptions\DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 503);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json([
                'error' => 'An unhandled error occured while attempting to modify this database\'s password.',
            ], 503);
        }
    }
}
