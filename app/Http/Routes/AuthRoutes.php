<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;
use Request;
use Pterodactyl\Models\User as User;

class AuthRoutes {

    public function map(Router $router) {
        $router->group([
            'prefix' => 'auth',
            'middleware' => [
                'guest'
            ]
        ], function () use ($router) {

            // Display Login Page
            $router->get('login', [
                'as' => 'auth.login',
                'uses' => 'Auth\AuthController@getLogin'
            ]);

            // Handle Login
            $router->post('login', [
                'uses' => 'Auth\AuthController@postLogin'
            ]);

            // Determine if we need to ask for a TOTP Token
            $router->post('login/totp', [
                'uses' => 'Auth\AuthController@checkTotp'
            ]);

            // Show Password Reset Form
            $router->get('password', [
                'as' => 'auth.password',
                'uses' => 'Auth\PasswordController@getEmail'
            ]);

            // Handle Password Reset
            $router->post('password', [
                'as' => 'auth.password.submit',
                'uses' => 'Auth\PasswordController@postEmail'
            ], function () {
                return redirect('auth/password')->with('sent', true);
            });

            // Show Verification Checkpoint
            $router->get('password/verify/{token}', [
                'as' => 'auth.verify',
                'uses' => 'Auth\PasswordController@getReset'
            ]);

            // Handle Verification
            $router->post('password/verify', [
                'uses' => 'Auth\PasswordController@postReset'
            ]);

        });

        // Not included above because we don't want the guest middleware
        $router->get('logout', [
            'as' => 'auth.logout',
            'middleware' => 'auth',
            'uses' => 'Auth\AuthController@getLogout'
        ]);

    }

}
