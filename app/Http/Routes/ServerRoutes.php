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
namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class ServerRoutes {

    public function map(Router $router) {
        $router->group([
            'prefix' => 'server/{server}',
            'middleware' => [
                'auth',
                'server',
                'csrf'
            ]
        ], function ($server) use ($router) {
            // Index View for Server
            $router->get('/', [
                'as' => 'server.index',
                'uses' => 'Server\ServerController@getIndex'
            ]);

            // Settings
            $router->get('/settings', [
                'as' => 'server.settings',
                'uses' => 'Server\ServerController@getSettings'
            ]);

            $router->post('/settings/sftp', [
                'as' => 'server.settings.sftp',
                'uses' => 'Server\ServerController@postSettingsSFTP'
            ]);

            // File Manager Routes
            $router->get('/files', [
                'as' => 'files.index',
                'uses' => 'Server\ServerController@getFiles'
            ]);

            $router->get('/files/edit/{file}', [
                'as' => 'files.edit',
                'uses' => 'Server\ServerController@getEditFile'
            ])->where('file', '.*');

            $router->get('/files/download/{file}', [
                'as' => 'files.download',
                'uses' => 'Server\ServerController@getDownloadFile'
            ])->where('file', '.*');

            $router->get('/files/add', [
                'as' => 'files.add',
                'uses' => 'Server\ServerController@getAddFile'
            ]);

            $router->post('files/directory-list', [
                'as' => 'server.files.directory-list',
                'uses' => 'Server\AjaxController@postDirectoryList'
            ]);

            $router->post('files/save', [
                'as' => 'server.files.save',
                'uses' => 'Server\AjaxController@postSaveFile'
            ]);

            // Sub-User Routes
            $router->get('users', [
                'as' => 'server.subusers',
                'uses' => 'Server\SubuserController@getIndex'
            ]);

            $router->get('users/new', [
                'as' => 'server.subusers.new',
                'uses' => 'Server\SubuserController@getNew'
            ]);

            $router->post('users/new', [
                'uses' => 'Server\SubuserController@postNew'
            ]);

            $router->get('users/view/{id}', [
                'as' => 'server.subusers.view',
                'uses' => 'Server\SubuserController@getView'
            ]);

            $router->post('users/view/{id}', [
                'uses' => 'Server\SubuserController@postView'
            ]);

            $router->delete('users/delete/{id}', [
                'uses' => 'Server\SubuserController@deleteSubuser'
            ]);

            // Assorted AJAX Routes
            $router->group(['prefix' => 'ajax'], function ($server) use ($router) {
                // Returns Server Status
                $router->get('status', [
                    'uses' => 'Server\AjaxController@getStatus'
                ]);

                // Sets the Default Connection for the Server
                $router->post('set-connection', [
                    'uses' => 'Server\AjaxController@postSetConnection'
                ]);
            });

            // Assorted AJAX Routes
            $router->group(['prefix' => 'js'], function ($server) use ($router) {
                // Returns Server Status
                $router->get('{file}', [
                    'as' => 'server.js',
                    'uses' => 'Server\ServerController@getJavascript'
                ])->where('file', '.*');

            });
        });
    }

}
