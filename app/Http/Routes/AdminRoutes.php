<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
                'uses' => 'Admin\ServersController@getIndex', ]);

            // View Create Server Page
            $router->get('/new', [
                'as' => 'admin.servers.new',
                'uses' => 'Admin\ServersController@getNew',
            ]);

            // Handle POST Request for Creating Server
            $router->post('/new', [
                'uses' => 'Admin\ServersController@postNewServer',
            ]);

            // Assorted Page Helpers
            $router->post('/new/get-nodes', [
                'uses' => 'Admin\ServersController@postNewServerGetNodes',
            ]);

            $router->post('/new/get-ips', [
                'uses' => 'Admin\ServersController@postNewServerGetIps',
            ]);

            $router->post('/new/service-options', [
                'uses' => 'Admin\ServersController@postNewServerServiceOptions',
            ]);

            $router->post('/new/option-details', [
                'uses' => 'Admin\ServersController@postNewServerOptionDetails'
            ]);
            // End Assorted Page Helpers

            // View Specific Server
            $router->get('/view/{id}', [
                'as' => 'admin.servers.view',
                'uses' => 'Admin\ServersController@getView',
            ]);

            // Database Stuffs
            $router->post('/view/{id}/database', [
                'as' => 'admin.servers.database',
                'uses' => 'Admin\ServersController@postDatabase',
            ]);

            // Change Server Details
            $router->post('/view/{id}/details', [
                'uses' => 'Admin\ServersController@postUpdateServerDetails',
            ]);

            // Change Server Details
            $router->post('/view/{id}/container', [
                'as' => 'admin.servers.post.container',
                'uses' => 'Admin\ServersController@postUpdateContainerDetails',
            ]);

            // Change Server Details
            $router->post('/view/{id}/startup', [
                'as' => 'admin.servers.post.startup',
                'uses' => 'Admin\ServersController@postUpdateServerStartup',
            ]);

            // Rebuild Server
            $router->post('/view/{id}/rebuild', [
                'uses' => 'Admin\ServersController@postUpdateServerToggleBuild',
            ]);

            // Change Build Details
            $router->post('/view/{id}/build', [
                'uses' => 'Admin\ServersController@postUpdateServerUpdateBuild',
            ]);

            // Suspend Server
            $router->post('/view/{id}/suspend', [
                'uses' => 'Admin\ServersController@postSuspendServer',
            ]);

            // Unsuspend Server
            $router->post('/view/{id}/unsuspend', [
                'uses' => 'Admin\ServersController@postUnsuspendServer',
            ]);

            // Change Install Status
            $router->post('/view/{id}/installed', [
                'uses' => 'Admin\ServersController@postToggleInstall',
            ]);

            // Delete [force delete]
            $router->delete('/view/{id}/{force?}', [
                'uses' => 'Admin\ServersController@deleteServer',
            ]);

            $router->post('/view/{id}/queuedDeletion', [
                'uses' => 'Admin\ServersController@postQueuedDeletionHandler',
                'as' => 'admin.servers.post.queuedDeletion',
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
                'uses' => 'Admin\NodesController@getIndex',
            ]);

            // Add New Node
            $router->get('/new', [
                'as' => 'admin.nodes.new',
                'uses' => 'Admin\NodesController@getNew',
            ]);

            $router->post('/new', [
                'uses' => 'Admin\NodesController@postNew',
            ]);

            // View Node
            $router->get('/view/{id}', [
                'as' => 'admin.nodes.view',
                'uses' => 'Admin\NodesController@getView',
            ]);

            $router->post('/view/{id}', [
                'uses' => 'Admin\NodesController@postView',
            ]);

            $router->delete('/view/{id}/deallocate/single/{allocation}', [
                'uses' => 'Admin\NodesController@deallocateSingle',
            ]);

            $router->post('/view/{id}/deallocate/block', [
                'uses' => 'Admin\NodesController@deallocateBlock',
            ]);

            $router->post('/view/{id}/alias', [
                'as' => 'admin.nodes.alias',
                'uses' => 'Admin\NodesController@setAlias',
            ]);

            $router->get('/view/{id}/allocations.json', [
                'as' => 'admin.nodes.view.allocations',
                'uses' => 'Admin\NodesController@getAllocationsJson',
            ]);

            $router->post('/view/{id}/allocations', [
                'as' => 'admin.nodes.post.allocations',
                'uses' => 'Admin\NodesController@postAllocations',
            ]);

            // View Deploy
            $router->get('/view/{id}/deploy', [
                'as' => 'admin.nodes.deply',
                'uses' => 'Admin\NodesController@getScript',
            ]);

            $router->delete('/view/{id}', [
                'as' => 'admin.nodes.delete',
                'uses' => 'Admin\NodesController@deleteNode',
            ]);
        });

        // Location Routes
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
                'uses' => 'Admin\LocationsController@getIndex',
            ]);
            $router->delete('/{id}', [
                'uses' => 'Admin\LocationsController@deleteLocation',
            ]);
            $router->patch('/{id}', [
                'uses' => 'Admin\LocationsController@patchLocation',
            ]);
            $router->post('/', [
                'uses' => 'Admin\LocationsController@postLocation',
            ]);
        });

        // Database Routes
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
                'uses' => 'Admin\DatabaseController@getIndex',
            ]);
            $router->get('/new', [
                'as' => 'admin.databases.new',
                'uses' => 'Admin\DatabaseController@getNew',
            ]);
            $router->post('/new', [
                'uses' => 'Admin\DatabaseController@postNew',
            ]);
            $router->delete('/delete/{id}', [
                'as' => 'admin.databases.delete',
                'uses' => 'Admin\DatabaseController@deleteDatabase',
            ]);
            $router->delete('/delete-server/{id}', [
                'as' => 'admin.databases.delete-server',
                'uses' => 'Admin\DatabaseController@deleteServer',
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
                'uses' => 'Admin\ServiceController@getIndex',
            ]);

            $router->get('/new', [
                'as' => 'admin.services.new',
                'uses' => 'Admin\ServiceController@getNew',
            ]);

            $router->post('/new', [
                'uses' => 'Admin\ServiceController@postNew',
            ]);

            $router->get('/service/{id}', [
                'as' => 'admin.services.service',
                'uses' => 'Admin\ServiceController@getService',
            ]);

            $router->post('/service/{id}', [
                'uses' => 'Admin\ServiceController@postService',
            ]);

            $router->delete('/service/{id}', [
                'uses' => 'Admin\ServiceController@deleteService',
            ]);

            $router->get('/service/{id}/configuration', [
                'as' => 'admin.services.service.config',
                'uses' => 'Admin\ServiceController@getConfiguration'
            ]);

            $router->post('/service/{id}/configuration', [
                'uses' => 'Admin\ServiceController@postConfiguration'
            ]);

            $router->get('/service/{service}/option/new', [
                'as' => 'admin.services.option.new',
                'uses' => 'Admin\ServiceController@newOption',
            ]);

            $router->post('/service/{service}/option/new', [
                'uses' => 'Admin\ServiceController@postNewOption',
            ]);

            $router->get('/service/{service}/option/{option}', [
                'as' => 'admin.services.option',
                'uses' => 'Admin\ServiceController@getOption',
            ]);

            $router->post('/service/{service}/option/{option}', [
                'uses' => 'Admin\ServiceController@postOption',
            ]);

            $router->delete('/service/{service}/option/{id}', [
                'uses' => 'Admin\ServiceController@deleteOption',
            ]);

            $router->get('/service/{service}/option/{option}/variable/new', [
                'as' => 'admin.services.option.variable.new',
                'uses' => 'Admin\ServiceController@getNewVariable',
            ]);

            $router->post('/service/{service}/option/{option}/variable/new', [
                'uses' => 'Admin\ServiceController@postNewVariable',
            ]);

            $router->post('/service/{service}/option/{option}/variable/{variable}', [
                'as' => 'admin.services.option.variable',
                'uses' => 'Admin\ServiceController@postOptionVariable',
            ]);

            $router->get('/service/{service}/option/{option}/variable/{variable}/delete', [
                'as' => 'admin.services.option.variable.delete',
                'uses' => 'Admin\ServiceController@deleteVariable',
            ]);
        });

        // Service Packs
        $router->group([
            'prefix' => 'admin/services/packs',
            'middleware' => [
                'auth',
                'admin',
                'csrf'
            ]
        ], function () use ($router) {
            $router->get('/new/{option?}', [
                'as' => 'admin.services.packs.new',
                'uses' => 'Admin\PackController@new'
            ]);
            $router->post('/new', [
                'uses' => 'Admin\PackController@create'
            ]);
            $router->get('/upload/{option?}', [
                'as' => 'admin.services.packs.uploadForm',
                'uses' => 'Admin\PackController@uploadForm'
            ]);
            $router->post('/upload', [
                'uses' => 'Admin\PackController@postUpload'
            ]);
            $router->get('/', [
                'as' => 'admin.services.packs',
                'uses' => 'Admin\PackController@listAll'
            ]);
            $router->get('/for/option/{option}', [
                'as' => 'admin.services.packs.option',
                'uses' => 'Admin\PackController@listByOption'
            ]);
            $router->get('/for/service/{service}', [
                'as' => 'admin.services.packs.service',
                'uses' => 'Admin\PackController@listByService'
            ]);
            $router->get('/edit/{pack}', [
                'as' => 'admin.services.packs.edit',
                'uses' => 'Admin\PackController@edit'
            ]);
            $router->post('/edit/{pack}', [
                'uses' => 'Admin\PackController@update'
            ]);
            $router->get('/edit/{pack}/export/{archive?}', [
                'as' => 'admin.services.packs.export',
                'uses' => 'Admin\PackController@export'
            ]);
        });
    }
}
