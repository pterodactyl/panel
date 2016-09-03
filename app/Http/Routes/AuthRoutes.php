<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
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
use Request;
use Pterodactyl\Models\User as User;

use Auth;
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
                'uses' => 'Auth\LoginController@showLoginForm'
            ]);

            // Handle Login
            $router->post('login', [
                'uses' => 'Auth\LoginController@login'
            ]);

            // Determine if we need to ask for a TOTP Token
            $router->post('login/totp', [
                'uses' => 'Auth\LoginController@checkTotp'
            ]);

            // Show Password Reset Form
            $router->get('password', [
                'as' => 'auth.password',
                'uses' => 'Auth\ForgotPasswordController@showLinkRequestForm'
            ]);

            // Handle Password Reset
            $router->post('password', [
                'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail'
            ]);

            // Show Verification Checkpoint
            $router->get('password/reset/{token}', [
                'as' => 'auth.reset',
                'uses' => 'Auth\ResetPasswordController@showResetForm'
            ]);

            // Handle Verification
            $router->post('password/reset', [
                'uses' => 'Auth\ResetPasswordController@reset'
            ]);

        });

        // Not included above because we don't want the guest middleware
        $router->get('auth/logout', [
            'as' => 'auth.logout',
            'middleware' => 'auth',
            'uses' => 'Auth\LoginController@logout'
        ]);

    }

}
