<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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

class AdminRoutes
{
    public function map(Router $router)
    {

        // Admin Index
        $router->get('admin', [
            'as' => 'admin.index',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
            'uses' => 'Admin\BaseController@getIndex',
        ]);

        $router->group([
            'prefix' => 'admin/databases',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'admin.databases',
                'uses' => 'Admin\DatabaseController@index',
            ]);

            $router->post('/', 'Admin\DatabaseController@create');

            $router->get('/view/{id}', [
                'as' => 'admin.databases.view',
                'uses' => 'Admin\DatabaseController@view',
            ]);

            $router->post('/view/{id}', 'Admin\DatabaseController@update');
        });

        $router->group([
            'prefix' => 'admin/locations',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'admin.locations',
                'uses' => 'Admin\LocationController@index',
            ]);

            $router->post('/', 'Admin\LocationController@create');

            $router->get('/view/{id}', [
                'as' => 'admin.locations.view',
                'uses' => 'Admin\LocationController@view',
            ]);

            $router->post('/view/{id}', 'Admin\LocationController@update');
        });

        $router->group([
            'prefix' => 'admin/settings',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'admin.settings',
                'uses' => 'Admin\BaseController@getSettings',
            ]);
            $router->post('/', [
                'uses' => 'Admin\BaseController@postSettings',
            ]);
        });

        $router->group([
            'prefix' => 'admin/users',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {

            // View All Accounts on System
            $router->get('/', [
                'as' => 'admin.users',
                'uses' => 'Admin\UserController@getIndex',
            ]);

            $router->get('/accounts.json', [
                'as' => 'admin.users.json',
                'uses' => 'Admin\UserController@getJson',
            ]);

            // View Specific Account
            $router->get('/view/{id}', [
                'as' => 'admin.users.view',
                'uses' => 'Admin\UserController@getView',
            ]);

            // View Specific Account
            $router->post('/view/{id}', [
                'uses' => 'Admin\UserController@updateUser',
            ]);

            // Delete an Account Matching an ID
            $router->delete('/view/{id}', [
                'uses' => 'Admin\UserController@deleteUser',
            ]);

            // Show Create Account Page
            $router->get('/new', [
                'as' => 'admin.users.new',
                'uses' => 'Admin\UserController@getNew',
            ]);

            // Handle Creating New Account
            $router->post('/new', [
                'uses' => 'Admin\UserController@postNew',
            ]);
        });

        // Server Routes
        $router->group([
            'prefix' => 'admin/servers',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {

            // View All Servers
            $router->get('/', [
                'as' => 'admin.servers',
                'uses' => 'Admin\ServersController@index',
            ]);

            // View Create Server Page
            $router->get('/new', [
                'as' => 'admin.servers.new',
                'uses' => 'Admin\ServersController@new',
            ]);

            // Handle POST Request for Creating Server
            $router->post('/new', [
                'uses' => 'Admin\ServersController@create',
            ]);

            // Assorted Page Helpers
            $router->post('/new/nodes', [
                'as' => 'admin.servers.new.nodes',
                'uses' => 'Admin\ServersController@newServerNodes',
            ]);

            $router->get('/view/{id}', [
                'as' => 'admin.servers.view',
                'uses' => 'Admin\ServersController@viewIndex',
            ]);

            $router->get('/view/{id}/details', [
                'as' => 'admin.servers.view.details',
                'uses' => 'Admin\ServersController@viewDetails',
            ]);

            $router->post('/view/{id}/details', [
                'uses' => 'Admin\ServersController@setDetails',
            ]);

            $router->post('/view/{id}/details/container', [
                'as' => 'admin.servers.view.details.container',
                'uses' => 'Admin\ServersController@setContainer',
            ]);

            $router->get('/view/{id}/build', [
                'as' => 'admin.servers.view.build',
                'uses' => 'Admin\ServersController@viewBuild',
            ]);

            $router->post('/view/{id}/build', [
                'uses' => 'Admin\ServersController@updateBuild',
            ]);

            $router->get('/view/{id}/startup', [
                'as' => 'admin.servers.view.startup',
                'uses' => 'Admin\ServersController@viewStartup',
            ]);

            $router->post('/view/{id}/startup', [
                'uses' => 'Admin\ServersController@saveStartup',
            ]);

            $router->get('/view/{id}/database', [
                'as' => 'admin.servers.view.database',
                'uses' => 'Admin\ServersController@viewDatabase',
            ]);

            $router->post('/view/{id}/database', [
                'uses' => 'Admin\ServersController@newDatabase',
            ]);

            $router->patch('/view/{id}/database', [
                'uses' => 'Admin\ServersController@resetDatabasePassword',
            ]);

            $router->delete('/view/{id}/database/{database}/delete', [
                'as' => 'admin.servers.view.database.delete',
                'uses' => 'Admin\ServersController@deleteDatabase',
            ]);

            $router->get('/view/{id}/manage', [
                'as' => 'admin.servers.view.manage',
                'uses' => 'Admin\ServersController@viewManage',
            ]);

            $router->post('/view/{id}/manage/toggle', [
                'as' => 'admin.servers.view.manage.toggle',
                'uses' => 'Admin\ServersController@toggleInstall',
            ]);

            $router->post('/view/{id}/manage/rebuild', [
                'as' => 'admin.servers.view.manage.rebuild',
                'uses' => 'Admin\ServersController@rebuildContainer',
            ]);

            $router->post('/view/{id}/manage/suspension', [
                'as' => 'admin.servers.view.manage.suspension',
                'uses' => 'Admin\ServersController@manageSuspension',
            ]);

            $router->get('/view/{id}/delete', [
                'as' => 'admin.servers.view.delete',
                'uses' => 'Admin\ServersController@viewDelete',
            ]);

            $router->post('/view/{id}/delete', [
                'uses' => 'Admin\ServersController@delete',
            ]);

            $router->post('/view/{id}/delete/continue/{force?}', [
                'as' => 'admin.servers.view.delete.continue',
                'uses' => 'Admin\ServersController@continueDeletion',
            ]);

            $router->post('/view/{id}/delete/cancel', [
                'as' => 'admin.servers.view.delete.cancel',
                'uses' => 'Admin\ServersController@cancelDeletion',
            ]);
        });

        // Node Routes
        $router->group([
            'prefix' => 'admin/nodes',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {

            // View All Nodes
            $router->get('/', [
                'as' => 'admin.nodes',
                'uses' => 'Admin\NodesController@index',
            ]);

            // Add New Node
            $router->get('/new', [
                'as' => 'admin.nodes.new',
                'uses' => 'Admin\NodesController@new',
            ]);

            $router->post('/new', [
                'uses' => 'Admin\NodesController@create',
            ]);

            $router->get('/view/{id}', [
                'as' => 'admin.nodes.view',
                'uses' => 'Admin\NodesController@viewIndex',
            ]);

            $router->get('/view/{id}/settings', [
                'as' => 'admin.nodes.view.settings',
                'uses' => 'Admin\NodesController@viewSettings',
            ]);

            $router->post('/view/{id}/settings', [
                'uses' => 'Admin\NodesController@updateSettings',
            ]);

            $router->get('/view/{id}/configuration', [
                'as' => 'admin.nodes.view.configuration',
                'uses' => 'Admin\NodesController@viewConfiguration',
            ]);

            $router->get('/view/{id}/allocation', [
                'as' => 'admin.nodes.view.allocation',
                'uses' => 'Admin\NodesController@viewAllocation',
            ]);

            $router->post('/view/{id}/allocation', [
                'uses' => 'Admin\NodesController@createAllocation',
            ]);

            $router->get('/view/{id}/servers', [
                'as' => 'admin.nodes.view.servers',
                'uses' => 'Admin\NodesController@viewServers',
            ]);

            $router->delete('/view/{id}/delete', [
                'as' => 'admin.nodes.view.delete',
                'uses' => 'Admin\NodesController@delete',
            ]);

            $router->delete('/view/{id}/allocation/remove/{allocation}', [
                'as' => 'admin.nodes.view.allocation.removeSingle',
                'uses' => 'Admin\NodesController@allocationRemoveSingle',
            ]);

            $router->post('/view/{id}/allocation/remove', [
                'as' => 'admin.nodes.view.allocation.removeBlock',
                'uses' => 'Admin\NodesController@allocationRemoveBlock',
            ]);

            $router->post('/view/{id}/allocation/alias', [
                'as' => 'admin.nodes.view.allocation.setAlias',
                'uses' => 'Admin\NodesController@allocationSetAlias',
            ]);

            $router->get('/view/{id}/settings/token', [
                'as' => 'admin.nodes.view.configuration.token',
                'uses' => 'Admin\NodesController@setToken',
            ]);
        });

        // Service Routes
        $router->group([
            'prefix' => 'admin/services',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'admin.services',
                'uses' => 'Admin\ServiceController@index',
            ]);

            $router->get('/new', [
                'as' => 'admin.services.new',
                'uses' => 'Admin\ServiceController@new',
            ]);

            $router->post('/new', [
                'uses' => 'Admin\ServiceController@create',
            ]);

            $router->get('/view/{id}', [
                'as' => 'admin.services.view',
                'uses' => 'Admin\ServiceController@view',
            ]);

            $router->post('/view/{id}', 'Admin\ServiceController@edit');

            $router->get('/view/{id}/functions', [
                'as' => 'admin.services.view.functions',
                'uses' => 'Admin\ServiceController@viewFunctions',
            ]);

            $router->delete('/view/{id}', [
                'uses' => 'Admin\ServiceController@delete',
            ]);

            // ---------------------
            // Service Option Routes
            // ---------------------
            $router->get('/option/new', [
                'as' => 'admin.services.option.new',
                'uses' => 'Admin\OptionController@new',
            ]);

            $router->post('/option/new', 'Admin\OptionController@create');

            $router->get('/option/{id}', [
                'as' => 'admin.services.option.view',
                'uses' => 'Admin\OptionController@viewConfiguration',
            ]);

            $router->post('/option/{id}', 'Admin\OptionController@editConfiguration');

            $router->get('/option/{id}/variables', [
                'as' => 'admin.services.option.variables',
                'uses' => 'Admin\OptionController@viewVariables',
            ]);

            $router->post('/option/{id}/variables', 'Admin\OptionController@createVariable');

            $router->post('/option/{id}/variables/{variable}', [
                'as' => 'admin.services.option.variables.edit',
                'uses' => 'Admin\OptionController@editVariable',
            ]);
        });

        // Service Packs
        $router->group([
            'prefix' => 'admin/packs',
            'middleware' => [
                'auth',
                'admin',
                'csrf',
            ],
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'admin.packs',
                'uses' => 'Admin\PackController@index',
            ]);

            $router->get('/new', [
                'as' => 'admin.packs.new',
                'uses' => 'Admin\PackController@new',
            ]);

            $router->post('/new', 'Admin\PackController@create');

            $router->get('/new/template', [
                'as' => 'admin.packs.new.template',
                'uses' => 'Admin\PackController@newTemplate',
            ]);

            $router->get('/view/{id}', [
                'as' => 'admin.packs.view',
                'uses' => 'Admin\PackController@view',
            ]);

            $router->post('/view/{id}', 'Admin\PackController@update');

            $router->post('/view/{id}/export/{files?}', [
                'as' => 'admin.packs.view.export',
                'uses' => 'Admin\PackController@export',
            ]);
        });
    }
}
