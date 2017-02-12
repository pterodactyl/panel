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

use DB;
use Log;
use Uuid;
use Alert;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServerRepository;
use Pterodactyl\Repositories\Daemon\FileRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServerController extends Controller
{
    /**
     * Controller Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Renders server index page for specified server.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getIndex(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);

        $server->js([
            'meta' => [
                'saveFile' => route('server.files.save', $server->uuidShort),
                'csrfToken' => csrf_token(),
            ],
        ]);

        return view('server.index', [
            'server' => $server,
            'node' => $server->node,
        ]);
    }

    /**
     * Renders file overview page.
     *
     * @param  Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getFiles(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('list-files', $server);

        $server->js([
            'meta' => [
                'directoryList' => route('server.files.directory-list', $server->uuidShort),
                'csrftoken' => csrf_token(),
            ],
            'permissions' => [
                'moveFiles' => $request->user()->can('move-files', $server),
                'copyFiles' => $request->user()->can('copy-files', $server),
                'compressFiles' => $request->user()->can('compress-files', $server),
                'decompressFiles' => $request->user()->can('decompress-files', $server),
                'createFiles' => $request->user()->can('create-files', $server),
                'downloadFiles' => $request->user()->can('download-files', $server),
                'deleteFiles' => $request->user()->can('delete-files', $server),
            ],
        ]);

        return view('server.files.index', [
            'server' => $server,
            'node' => $server->node,
        ]);
    }

    /**
     * Renders add file page.
     *
     * @param  Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function getAddFile(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('add-files', $server);

        $server->js();

        return view('server.files.add', [
            'server' => $server,
            'node' => $server->node,
            'directory' => (in_array($request->get('dir'), [null, '/', ''])) ? '' : trim($request->get('dir'), '/') . '/',
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
        $server = Models\Server::byUuid($uuid);
        $this->authorize('edit-files', $server);

        $fileInfo = (object) pathinfo($file);
        $controller = new FileRepository($uuid);

        try {
            $fileContent = $controller->returnFileContents($file);
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();

            return redirect()->route('server.files.index', $uuid);
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An error occured while attempting to load the requested file for editing, please try again.')->flash();

            return redirect()->route('server.files.index', $uuid);
        }

        $server->js([
            'stat' => $fileContent['stat'],
        ]);

        return view('server.files.edit', [
            'server' => $server,
            'node' => $server->node,
            'file' => $file,
            'stat' => $fileContent['stat'],
            'contents' => $fileContent['file']->content,
            'directory' => (in_array($fileInfo->dirname, ['.', './', '/'])) ? '/' : trim($fileInfo->dirname, '/') . '/',
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
        $server = Models\Server::byUuid($uuid);
        $this->authorize('download-files', $server);

        $download = new Models\Download;

        $download->token = (string) Uuid::generate(4);
        $download->server = $server->uuid;
        $download->path = $file;

        $download->save();

        return redirect($server->node->scheme . '://' . $server->node->fqdn . ':' . $server->node->daemonListen . '/server/file/download/' . $download->token);
    }

    public function getAllocation(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('view-allocation', $server);
        $server->js();

        return view('server.settings.allocation', [
            'server' => $server->load(['allocations' => function ($query) {
                $query->orderBy('ip', 'asc');
                $query->orderBy('port', 'asc');
            }]),
            'node' => $server->node,
        ]);
    }

    public function getStartup(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $server->load(['allocations' => function ($query) use ($server) {
            $query->where('id', $server->allocation_id);
        }]);
        $this->authorize('view-startup', $server);

        $variables = Models\ServiceVariable::select(
                'service_variables.*',
                DB::raw('COALESCE(server_variables.variable_value, service_variables.default_value) as a_serverValue')
            )->leftJoin('server_variables', 'server_variables.variable_id', '=', 'service_variables.id')
            ->where('service_variables.option_id', $server->option_id)
            ->where('server_variables.server_id', $server->id)
            ->get();

        $service = Models\Service::select(
                DB::raw('IFNULL(service_options.executable, services.executable) as executable')
            )->leftJoin('service_options', 'service_options.service_id', '=', 'services.id')
            ->where('service_options.id', $server->option_id)
            ->where('services.id', $server->service_id)
            ->first();

        $allocation = $server->allocations->pop();
        $ServerVariable = [
            '{{SERVER_MEMORY}}' => $server->memory,
            '{{SERVER_IP}}' => $allocation->ip,
            '{{SERVER_PORT}}' => $allocation->port,
        ];

        $processed = str_replace(array_keys($ServerVariable), array_values($ServerVariable), $server->startup);
        foreach ($variables as &$variable) {
            $replace = ($variable->user_viewable === 1) ? $variable->a_serverValue : '[hidden]';
            $processed = str_replace('{{' . $variable->env_variable . '}}', $replace, $processed);
        }

        $server->js();

        return view('server.settings.startup', [
            'server' => $server,
            'node' => $server->node,
            'variables' => $variables->where('user_viewable', 1),
            'service' => $service,
            'processedStartup' => $processed,
        ]);
    }

    public function getDatabases(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('view-databases', $server);
        $server->js();

        return view('server.settings.databases', [
            'server' => $server,
            'node' => $server->node,
            'databases' => Models\Database::select('databases.*', 'database_servers.host as a_host', 'database_servers.port as a_port')
                ->where('server_id', $server->id)
                ->join('database_servers', 'database_servers.id', '=', 'databases.db_server')
                ->get(),
        ]);
    }

    public function getSFTP(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('view-sftp', $server);
        $server->js();

        return view('server.settings.sftp', [
            'server' => $server,
            'node' => $server->node,
        ]);
    }

    public function postSettingsSFTP(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('reset-sftp', $server);

        try {
            $repo = new ServerRepository;
            $repo->updateSFTPPassword($server->id, $request->input('sftp_pass'));
            Alert::success('Successfully updated this servers SFTP password.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.settings.sftp', $uuid)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unknown error occured while attempting to update this server\'s SFTP settings.')->flash();
        }

        return redirect()->route('server.settings.sftp', $uuid);
    }

    public function postSettingsStartup(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('edit-startup', $server);

        try {
            $repo = new ServerRepository;
            $repo->updateStartup($server->id, $request->except([
                '_token',
            ]));
            Alert::success('Server startup variables were successfully updated.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update startup variables for this server. Please try again.')->flash();
        }

        return redirect()->route('server.settings', [
            'uuid' => $uuid,
            'tab' => 'tab_startup',
        ]);
    }
}
