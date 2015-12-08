<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class AdminRoutes {

    public function map(Router $router) {
        $router->group(['prefix' => 'admin'], function ($server) use ($router) {
            $router->get('/', [ 'as' => 'admin.index', 'uses' => 'Admin\BaseController@getIndex' ]);

            // Account Routes
            $router->group(['prefix' => 'accounts'], function ($server) use ($router) {
                $router->get('/', [ 'as' => 'admin.accounts', 'uses' => 'Admin\AccountsController@getIndex' ]);
                $router->get('/new', [ 'as' => 'admin.accounts.new', 'uses' => 'Admin\AccountsController@getNew' ]);
                $router->get('/view/{id}', [ 'as' => 'admin.accounts.view', 'uses' => 'Admin\AccountsController@getView' ]);
            });

            // Server Routes
            $router->group(['prefix' => 'servers'], function ($server) use ($router) {
                $router->get('/', [ 'as' => 'admin.servers', 'uses' => 'Admin\ServersController@getIndex' ]);
                $router->get('/new', [ 'as' => 'admin.servers.new', 'uses' => 'Admin\ServersController@getNew' ]);
                $router->get('/view/{id}', [ 'as' => 'admin.servers.view', 'uses' => 'Admin\ServersController@getView' ]);
            });

            // AJAX Routes
            $router->group(['prefix' => 'ajax'], function ($server) use ($router) {
                $router->post('/new/server/get-nodes', [ 'uses' => 'Admin\AjaxController@postNewServerGetNodes' ]);
                $router->post('/new/server/get-ips', [ 'uses' => 'Admin\AjaxController@postNewServerGetIps' ]);
                $router->post('/new/server/service-options', [ 'uses' => 'Admin\AjaxController@postNewServerServiceOptions' ]);
            });

        });
    }

}
