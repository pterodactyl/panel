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
use Alert;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServerController extends Controller
{
    /**
     * Returns the allocation overview for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\View\View
     */
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

    /**
     * Returns the startup overview for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\View\View
     */
    public function getStartup(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('view-startup', $server);

        $server->load(['node', 'allocation', 'variables']);
        $variables = Models\ServiceVariable::where('option_id', $server->option_id)->get();

        $replacements = [
            '{{SERVER_MEMORY}}' => $server->memory,
            '{{SERVER_IP}}' => $server->allocation->ip,
            '{{SERVER_PORT}}' => $server->allocation->port,
        ];

        $processed = str_replace(array_keys($replacements), array_values($replacements), $server->startup);

        foreach ($variables as $var) {
            if ($var->user_viewable) {
                $serverVar = $server->variables->where('variable_id', $var->id)->first();
                $var->server_set_value = $serverVar->variable_value ?? $var->default_value;
            } else {
                $var->server_set_value = '[hidden]';
            }

            $processed = str_replace('{{' . $var->env_variable . '}}', $var->server_set_value, $processed);
        }

        $server->js();

        return view('server.settings.startup', [
            'server' => $server,
            'node' => $server->node,
            'variables' => $variables->where('user_viewable', 1),
            'service' => $server->service,
            'processedStartup' => $processed,
        ]);
    }

    /**
     * Returns the database overview for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\View\View
     */
    public function getDatabases(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('view-databases', $server);

        $server->load('node', 'databases.host');
        $server->js();

        return view('server.settings.databases', [
            'server' => $server,
            'node' => $server->node,
            'databases' => $server->databases,
        ]);
    }

    /**
     * Returns the SFTP overview for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\View\View
     */
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

    /**
     * Handles changing the SFTP password for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Handles changing the startup settings for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSettingsStartup(Request $request, $uuid)
    {
        $server = Models\Server::byUuid($uuid);
        $this->authorize('edit-startup', $server);

        try {
            $repo = new ServerRepository;
            $repo->updateStartup($server->id, $request->except('_token'));
            Alert::success('Server startup variables were successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('server.settings.startup', $uuid)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update startup variables for this server. Please try again.')->flash();
        }

        return redirect()->route('server.settings.startup', $uuid);
    }
}
