<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class AuthRoutes {

    public function map(Router $router) {
        $router->group(['prefix' => 'auth'], function () use ($router) {
            $router->get('login', [ 'as' => 'auth.login', 'uses' => 'Auth\AuthController@getLogin' ]);
            $router->post('login', [ 'as' => 'auth.login.submit', 'uses' => 'Auth\AuthController@postLogin' ]);

            $router->get('logout', [ 'as' => 'auth.logout', 'uses' => 'Auth\AuthController@getLogout' ]);
        });
    }

}