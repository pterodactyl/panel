<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class RestRoutes {

    public function map(Router $router) {
        $router->group([
            'prefix' => 'api/v1',
            'middleware' => [
                'api'
            ]
        ], function () use ($router) {
            // Users endpoint for API
            $router->group(['prefix' => 'users'], function () use ($router) {
                // Returns all users
                $router->get('/', [
                    'uses' => 'API\UserController@getAllUsers'
                ]);

                // Return listing of user [with only specified fields]
                $router->get('/{id}/{fields?}', [
                    'uses' => 'API\UserController@getUser'
                ])->where('id', '[0-9]+');
            });
        });
    }

}
