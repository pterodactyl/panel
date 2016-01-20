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

class BaseRoutes {

    public function map(Router $router) {

        // Index of Panel
        $router->get('/', [
            'as' => 'index',
            'middleware' => 'auth',
            'uses' => 'Base\IndexController@getIndex'
        ]);

        // Handle Index. Redirect /index to /
        $router->get('/index', function () {
            return redirect()->route('index');
        });

        // Password Generation
        $router->get('/password-gen/{length}', [
            'as' => 'password-gen',
            'middleware' => 'auth',
            'uses' => 'Base\IndexController@getPassword'
        ]);

        // Account Routes
        $router->group([
            'profix' => 'account',
            'middleware' => [
                'auth',
                'csrf'
            ]
        ], function () use ($router) {
            $router->get('account', [
                'as' => 'account',
                'uses' => 'Base\IndexController@getAccount'
            ]);
            $router->post('/account/password', [
                'uses' => 'Base\IndexController@postAccountPassword'
            ]);
            $router->post('/account/email', [
                'uses' => 'Base\IndexController@postAccountEmail'
            ]);
        });

        // TOTP Routes
        $router->group([
            'prefix' => 'account/totp',
            'middleware' => [
                'auth',
                'csrf'
            ]
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'account.totp',
                'uses' => 'Base\IndexController@getAccountTotp'
            ]);
            $router->put('/', [
                'uses' => 'Base\IndexController@putAccountTotp'
            ]);
            $router->post('/', [
                'uses' => 'Base\IndexController@postAccountTotp'
            ]);
            $router->delete('/', [
                'uses' => 'Base\IndexController@deleteAccountTotp'
            ]);
        });

    }

}
