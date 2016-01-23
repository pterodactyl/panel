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

use Auth;
use Debugbar;
use Uuid;
use Alert;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Repositories\Daemon\FileRepository;
use Pterodactyl\Repositories\ServerRepository;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServerController extends Controller
{

    /**
     * Controller Constructor
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getJavascript(Request $request, $uuid, $file)
    {
        $server = Models\Server::getByUUID($uuid);
        return response()->view('server.js.' . $server->a_serviceFile . '.' . basename($file, '.js'), [
            'server' => $server,
            'node' => Models\Node::find($server->node)
        ])->header('Content-Type', 'application/javascript');
    }

    /**
     * Renders server index page for specified server.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getIndex(Request $request)
    {
        $server = Models\Server::getByUUID($request->route()->server);
        return view('server.index', [
            'server' => $server,
            'allocations' => Models\Allocation::where('assigned_to', $server->id)->orderBy('ip', 'asc')->orderBy('port', 'asc')->get(),
            'node' => Models\Node::find($server->node)
        ]);
    }

    /**
     * Renders file overview page.
     *
     * @param  Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getFiles(Request $request)
    {

        $server = Models\Server::getByUUID($request->route()->server);
        $this->authorize('list-files', $server);

        return view('server.files.index', [
            'server' => $server,
            'node' => Models\Node::find($server->node)
        ]);
    }

    /**
     * Renders add file page.
     *
     * @param  Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getAddFile(Request $request)
    {

        $server = Models\Server::getByUUID($request->route()->server);
        $this->authorize('add-files', $server);

        return view('server.files.add', [
            'server' => $server,
            'node' => Models\Node::find($server->node),
            'directory' => (in_array($request->get('dir'), [null, '/', ''])) ? '' : trim($request->get('dir'), '/') . '/'
        ]);
    }

    /**
     * Renders edit file page for a given file.
     *
     * @param  Request $request
     * @param  string  $uuid
     * @param  string  $file
     * @return \Illuminate\Contracts\View\View
     */
    public function getEditFile(Request $request, $uuid, $file)
    {

        $server = Models\Server::getByUUID($uuid);
        $this->authorize('edit-files', $server);

        $fileInfo = (object) pathinfo($file);
        $controller = new FileRepository($uuid);

        try {
            $fileContent = $controller->returnFileContents($file);
        } catch (\Exception $e) {

            Debugbar::addException($e);
            $exception = 'An error occured while attempting to load the requested file for editing, please try again.';

            if ($e instanceof DisplayException) {
                $exception = $e->getMessage();
            }

            Alert::danger($exception)->flash();
            return redirect()->route('files.index', $uuid);

        }

        return view('server.files.edit', [
            'server' => $server,
            'node' => Models\Node::find($server->node),
            'file' => $file,
            'contents' => $fileContent->content,
            'directory' => (in_array($fileInfo->dirname, ['.', './', '/'])) ? '/' : trim($fileInfo->dirname, '/') . '/',
            'extension' => $fileInfo->extension
        ]);

    }

    /**
     * Handles downloading a file for the user.
     *
     * @param  Request $request
     * @param  string  $uuid
     * @param  string  $file
     * @return \Illuminate\Contracts\View\View
     */
    public function getDownloadFile(Request $request, $uuid, $file)
    {

        $server = Models\Server::getByUUID($uuid);
        $node = Models\Node::find($server->node);

        $this->authorize('download-files', $server);

        $download = new Download;

        $download->token = Uuid::generate(4);
        $download->server = $server->uuid;
        $download->path = str_replace('../', '', $file);

        $download->save();

        return redirect( $node->scheme . '://' . $node->fqdn . ':' . $node->daemonListen . '/server/download/' . $download->token);

    }

    /**
     * Renders server settings page.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getSettings(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $variables = Models\ServiceVariables::select('service_variables.*', 'server_variables.variable_value as a_serverValue')
            ->join('server_variables', 'server_variables.variable_id', '=', 'service_variables.id')
            ->where('service_variables.option_id', $server->option)
            ->where('server_variables.server_id', $server->id)
            ->get();
        $service = Models\Service::findOrFail($server->service);

        $serverVariables = [
            '{{SERVER_MEMORY}}' => $server->memory,
            '{{SERVER_IP}}' => $server->ip,
            '{{SERVER_PORT}}' => $server->port,
        ];

        $processed = str_replace(array_keys($serverVariables), array_values($serverVariables), $server->startup);
        foreach($variables as &$variable) {
            $replace = ($variable->user_viewable === 1) ? $variable->a_serverValue : '**';
            $processed = str_replace('{{' . $variable->env_variable . '}}', $replace, $processed);
        }

        return view('server.settings', [
            'server' => $server,
            'node' => Models\Node::find($server->node),
            'variables' => $variables,
            'service' => $service,
            'processedStartup' => $processed,
        ]);
    }

    public function postSettingsSFTP(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('reset-sftp', $server);

        try {
            $repo = new ServerRepository;
            $repo->updateSFTPPassword($server->id, $request->input('sftp_pass'));
            Alert::success('Successfully updated this servers SFTP password.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.settings', $uuid)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to update this server\'s SFTP settings.')->flash();
        }
        return redirect()->route('server.settings', $uuid);
    }

    public function postSettingsStartup(Request $request, $uuid)
    {
        $server = Models\Server::getByUUID($uuid);
        $this->authorize('edit-startup', $server);

        try {
            $repo = new ServerRepository;
            $repo->updateStartup($server->id, $request->except([
                '_token'
            ]));
            Alert::success('Server startup variables were successfully updated.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch(\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update startup variables for this server. Please try again.')->flash();
        } finally {
            return redirect()->route('server.settings', [
                'uuid' => $uuid,
                'tab' => 'tab_startup'
            ])->withInput();
        }
    }

}
