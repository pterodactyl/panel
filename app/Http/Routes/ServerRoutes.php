<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
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

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class ServerRoutes
{
    public function map(Router $router)
    {
        $router->group([
            'prefix' => 'server/{server}',
            'middleware' => [
                'auth',
                'server',
                'csrf',
            ],
        ], function ($server) use ($router) {

            // Index View for Server
            $router->get('/', [
                'as' => 'server.index',
                'uses' => 'Server\ServerController@getIndex',
            ]);

            // Settings
            $router->get('/settings', [
                'as' => 'server.settings',
                'uses' => 'Server\ServerController@getSettings',
            ]);

            $router->get('/settings/databases', [
                'as' => 'server.settings.databases',
                'uses' => 'Server\ServerController@getDatabases',
            ]);

            $router->get('/settings/sftp', [
                'as' => 'server.settings.sftp',
                'uses' => 'Server\ServerController@getSFTP',
            ]);

            $router->post('/settings/sftp', [
                'uses' => 'Server\ServerController@postSettingsSFTP',
            ]);

            $router->post('/settings/startup', [
                'as' => 'server.settings.startup',
                'uses' => 'Server\ServerController@postSettingsStartup',
            ]);

            // File Manager Routes
            $router->get('/files', [
                'as' => 'server.files.index',
                'uses' => 'Server\ServerController@getFiles',
            ]);

            $router->get('/files/edit/{file}', [
                'as' => 'server.files.edit',
                'uses' => 'Server\ServerController@getEditFile',
            ])->where('file', '.*');

            $router->get('/files/download/{file}', [
                'as' => 'server.files.download',
                'uses' => 'Server\ServerController@getDownloadFile',
            ])->where('file', '.*');

            $router->get('/files/add', [
                'as' => 'server.files.add',
                'uses' => 'Server\ServerController@getAddFile',
            ]);

            $router->post('files/directory-list', [
                'as' => 'server.files.directory-list',
                'uses' => 'Server\AjaxController@postDirectoryList',
            ]);

            $router->post('files/save', [
                'as' => 'server.files.save',
                'uses' => 'Server\AjaxController@postSaveFile',
            ]);

            // Sub-User Routes
            $router->get('users', [
                'as' => 'server.subusers',
                'uses' => 'Server\SubuserController@getIndex',
            ]);

            $router->get('users/new', [
                'as' => 'server.subusers.new',
                'uses' => 'Server\SubuserController@getNew',
            ]);

            $router->post('users/new', [
                'uses' => 'Server\SubuserController@postNew',
            ]);

            $router->get('users/view/{id}', [
                'as' => 'server.subusers.view',
                'uses' => 'Server\SubuserController@getView',
            ]);

            $router->post('users/view/{id}', [
                'uses' => 'Server\SubuserController@postView',
            ]);

            $router->delete('users/delete/{id}', [
                'uses' => 'Server\SubuserController@deleteSubuser',
            ]);

            $router->get('tasks/', [
                'as' => 'server.tasks',
                'uses' => 'Server\TaskController@getIndex',
            ]);

            $router->get('tasks/view/{id}', [
                'as' => 'server.tasks.view',
                'uses' => 'Server\TaskController@getView',
            ]);

            $router->get('tasks/new', [
                'as' => 'server.tasks.new',
                'uses' => 'Server\TaskController@getNew',
            ]);

            $router->post('tasks/new', [
                'uses' => 'Server\TaskController@postNew',
            ]);

            $router->delete('tasks/delete/{id}', [
                'as' => 'server.tasks.delete',
                'uses' => 'Server\TaskController@deleteTask',
            ]);

            $router->post('tasks/toggle/{id}', [
                'as' => 'server.tasks.toggle',
                'uses' => 'Server\TaskController@toggleTask',
            ]);

            // Assorted AJAX Routes
            $router->group(['prefix' => 'ajax'], function ($server) use ($router) {
                // Returns Server Status
                $router->get('status', [
                    'as' => 'server.ajax.status',
                    'uses' => 'Server\AjaxController@getStatus',
                ]);

                // Sets the Default Connection for the Server
                $router->post('set-primary', [
                    'uses' => 'Server\AjaxController@postSetPrimary',
                ]);

                $router->post('settings/reset-database-password', [
                    'as' => 'server.ajax.reset-database-password',
                    'uses' => 'Server\AjaxController@postResetDatabasePassword',
                ]);
            });
        });
    }
}
