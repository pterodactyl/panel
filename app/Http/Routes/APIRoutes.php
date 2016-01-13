<?php

namespace Pterodactyl\Http\Routes;

use Pterodactyl\Models;
use Illuminate\Routing\Router;

class APIRoutes
{

    public function map(Router $router) {

        app('Dingo\Api\Auth\Auth')->extend('jwt', function ($app) {
            return new \Dingo\Api\Auth\Provider\JWT($app['Tymon\JWTAuth\JWTAuth']);
        });

        $api = app('Dingo\Api\Routing\Router');

        $api->version('v1', function ($api) {
            $api->post('auth/login', [
                'as' => 'api.auth.login',
                'uses' => 'Pterodactyl\Http\Controllers\API\AuthController@postLogin'
            ]);

            $api->post('auth/validate', [
                'middleware' => 'api.auth',
                'as' => 'api.auth.validate',
                'uses' => 'Pterodactyl\Http\Controllers\API\AuthController@postValidate'
            ]);
        });

        $api->version('v1', ['middleware' => 'api.auth'], function ($api) {

            $api->get('users', [
                'as' => 'api.users',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@getUsers'
            ]);

            $api->post('users', [
                'as' => 'api.users.post',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@postUsers'
            ]);

            $api->get('users/{id}/{fields?}', [
                'as' => 'api.users.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@getUserByID'
            ]);

        });
    }

}
