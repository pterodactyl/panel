<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;
use Request;
use Pterodactyl\Models\User as User;

class AuthRoutes {

    public function map(Router $router) {
        $router->group([
            'prefix' => 'auth',
            'middleware' => [
                'guest',
                'csrf'
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
                'uses' => 'Auth\PasswordController@postEmail'
            ]);

            // Show Verification Checkpoint
            $router->get('password/reset/{token}', [
                'as' => 'auth.reset',
                'uses' => 'Auth\PasswordController@getReset'
            ]);

            // Handle Verification
            $router->post('password/reset', [
                'uses' => 'Auth\PasswordController@postReset'
            ]);

        });

        // Not included above because we don't want the guest middleware
        $router->get('auth/logout', [
            'as' => 'auth.logout',
            'middleware' => 'auth',
            'uses' => 'Auth\AuthController@getLogout'
        ]);

    }

}
