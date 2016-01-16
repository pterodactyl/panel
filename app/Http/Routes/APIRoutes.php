<?php

namespace Pterodactyl\Http\Routes;

use Pterodactyl\Models;
use Illuminate\Routing\Router;

class APIRoutes
{

    public function map(Router $router) {

        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', ['middleware' => 'api.auth'], function ($api) {

            /**
             * User Routes
             */
            $api->get('users', [
                'as' => 'api.users',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@getUsers'
            ]);

            $api->post('users', [
                'as' => 'api.users.post',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@postUser'
            ]);

            $api->get('users/{id}', [
                'as' => 'api.users.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@getUser'
            ]);

            $api->patch('users/{id}', [
                'as' => 'api.users.patch',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@patchUser'
            ]);

            $api->delete('users/{id}', [
                'as' => 'api.users.delete',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@deleteUser'
            ]);

            /**
             * Server Routes
             */
            $api->get('servers', [
                'as' => 'api.servers',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@getServers'
            ]);

            $api->get('servers/{id}', [
                'as' => 'api.servers.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@getServer'
            ]);

            /**
             * Node Routes
             */
            $api->get('nodes', [
                'as' => 'api.nodes',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@getNodes'
            ]);

            $api->post('nodes', [
                'as' => 'api.nodes.post',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@postNode'
            ]);

            $api->get('nodes/{id}', [
                'as' => 'api.nodes.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@getNode'
            ]);

            $api->get('nodes/{id}/allocations', [
                'as' => 'api.nodes.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@getNodeAllocations'
            ]);

            /**
             * Location Routes
             */
            $api->get('locations', [
                'as' => 'api.locations',
                'uses' => 'Pterodactyl\Http\Controllers\API\LocationController@getLocations'
            ]);

        });
    }

}
