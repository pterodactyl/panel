<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;
use Request;
use Pterodactyl\Models\User as User;

class AuthRoutes {

    public function map(Router $router) {
        $router->group(['prefix' => 'auth'], function () use ($router) {

            $router->get('login', [ 'as' => 'auth.login', 'uses' => 'Auth\AuthController@getLogin' ]);
            $router->post('login', [ 'uses' => 'Auth\AuthController@postLogin' ]);
            $router->post('login/totp', [ 'uses' => 'Auth\AuthController@checkTotp' ]);


            $router->get('password', [ 'as' => 'auth.password', 'uses' => 'Auth\PasswordController@getEmail' ]);
            $router->post('password', [ 'as' => 'auth.password.submit', 'uses' => 'Auth\PasswordController@postEmail' ], function () {
                return redirect('auth/password')->with('sent', true);
            });
            $router->post('password/verify', [ 'uses' => 'Auth\PasswordController@postReset' ]);
            $router->get('password/verify/{token}', [ 'as' => 'auth.verify', 'uses' => 'Auth\PasswordController@getReset' ]);

            $router->get('logout', [ 'as' => 'auth.logout', 'uses' => 'Auth\AuthController@getLogout' ]);

        });
    }

}
