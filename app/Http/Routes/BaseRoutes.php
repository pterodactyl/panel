<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class BaseRoutes {

    public function map(Router $router) {

        // Handle Index. Redirect /index to /
        $router->get('/', [ 'as' => 'index', 'uses' => 'Base\IndexController@getIndex' ]);
        $router->get('/index', function () {
            return redirect()->route('index');
        });

        // Account Routes
        $router->get('/account', [ 'as' => 'account', 'uses' => 'Base\IndexController@getAccount' ]);
        $router->post('/account/password', [ 'uses' => 'Base\IndexController@postAccountPassword' ]);
        $router->post('/account/email', [ 'uses' => 'Base\IndexController@postAccountEmail' ]);

        // TOTP Routes
        $router->get('/account/totp', [ 'as' => 'account.totp', 'uses' => 'Base\IndexController@getAccountTotp' ]);
        $router->put('/account/totp', [ 'uses' => 'Base\IndexController@putAccountTotp' ]);
        $router->post('/account/totp', [ 'uses' => 'Base\IndexController@postAccountTotp' ]);
        $router->delete('/account/totp', [ 'uses' => 'Base\IndexController@deleteAccountTotp' ]);

    }

}
