<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;
use Request;

class RemoteRoutes {

    public function map(Router $router) {
        $router->group(['prefix' => 'remote'], function () use ($router) {
            $router->post('download', [ 'as' => 'remote.download', 'uses' => 'Remote\RemoteController@postDownload' ]);
        });
    }

}
