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
namespace Pterodactyl\Http\Controllers\Server;

use Log;
use Debugbar;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
     * Controller Constructor
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

        $server = Server::getByUUID($uuid);

        if (!$server) {
            return response()->json([], 404);
        }

        $client = Node::guzzleRequest($server->node);

        try {
            $res = $client->request('GET', '/server', [
                'headers' => Server::getGuzzleHeaders($uuid)
            ]);
            if($res->getStatusCode() === 200) {
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

        $server = Server::getByUUID($uuid);
        $this->directory = '/' . trim(urldecode($request->input('directory', '/')), '/');
        $this->authorize('list-files', $server);

        $prevDir = [
            'header' => ($this->directory !== '/') ? $this->directory : ''
        ];
        if ($this->directory !== '/') {
            $prevDir['first'] = true;
        }

        // Determine if we should show back links in the file browser.
        // This code is strange, and could probably be rewritten much better.
        $goBack = explode('/', rtrim($this->directory, '/'));
        if (isset($goBack[2]) && !empty($goBack[2])) {
            $prevDir['show'] = true;
            $prevDir['link'] = '/' . trim(str_replace(end($goBack), '', $this->directory), '/');
            $prevDir['link_show'] = trim($prevDir['link'], '/');
        }

        $controller = new Repositories\Daemon\FileRepository($uuid);

        try {
            $directoryContents = $controller->returnDirectoryListing($this->directory);
        } catch (\Exception $e) {

            Debugbar::addException($e);
            $exception = 'An error occured while attempting to load the requested directory, please try again.';

            if ($e instanceof DisplayException) {
                $exception = $e->getMessage();
            }

            return response($exception, 500);

        }

        return view('server.files.list', [
            'server' => $server,
            'files' => $directoryContents->files,
            'folders' => $directoryContents->folders,
            'extensions' => Repositories\HelperRepository::editableFiles(),
            'directory' => $prevDir
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

        $server = Server::getByUUID($uuid);
        $this->authorize('save-files', $server);

        $controller = new Repositories\Daemon\FileRepository($uuid);

        try {
            $controller->saveFileContents($request->input('file'), $request->input('contents'));
            return response(null, 204);
        } catch (\Exception $e) {

            Debugbar::addException($e);
            $exception = 'An error occured while attempting to save that file, please try again.';

            if ($e instanceof DisplayException) {
                $exception = $e->getMessage();
            }

            return response($exception, 500);

        }

    }

    /**
     * [postSetConnection description]
     * @param  Request $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function postSetConnection(Request $request, $uuid)
    {

        $server = Server::getByUUID($uuid);
        $this->authorize('set-connection', $server);

        if ($request->input('connection') === $server->ip . ':' . $server->port) {
            return response()->json([
                'error' => 'You are already using this as your default connection.'
            ], 409);
        }

        try {
            $repo = new Repositories\ServerRepository;
            $repo->changeBuild($server->id, [
                'default' => $request->input('connection'),
            ]);
            return response('The default connection for this server has been updated. Please be aware that you will need to restart your server for this change to go into effect.');
        } catch (\Pterodactyl\Exceptions\DisplayValidationException $ex) {
            return response()->json([
                'error' => json_decode($ex->getMessage(), true),
            ], 503);
        } catch (\Pterodactyl\Exceptions\DisplayException $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 503);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'error' => 'An unhandled exception occured while attemping to modify the default connection for this server.'
            ], 503);
        }
    }

}
