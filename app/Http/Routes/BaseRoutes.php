<?php

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
                'auth'
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
                'auth'
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
