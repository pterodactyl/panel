<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class ServerRoutes {

    public function map(Router $router) {
        $router->group(['prefix' => 'server/{server}'], function ($server) use ($router) {

            $router->get('/', [ 'as' => 'server.index', 'uses' => 'Server\ServerController@getIndex' ]);
            $router->get('/files', [ 'as' => 'files.index', 'uses' => 'Server\ServerController@getFiles' ]);
            $router->get('/files/edit/{file}', [ 'as' => 'files.edit', 'uses' => 'Server\ServerController@getEditFile' ])->where('file', '.*');
            $router->get('/files/download/{file}', [ 'as' => 'files.download', 'uses' => 'Server\ServerController@getDownloadFile' ])->where('file', '.*');
            $router->get('/files/add', [ 'as' => 'files.add', 'uses' => 'Server\ServerController@getAddFile' ]);

            // Ajax Routes
            $router->group(['prefix' => 'ajax'], function ($server) use ($router) {
                $router->get('status', [ 'uses' => 'Server\AjaxController@getStatus' ]);
                $router->post('set-connection', [ 'uses' => 'Server\AjaxController@postSetConnection' ]);
                $router->post('files/directory-list', [ 'uses' => 'Server\AjaxController@postDirectoryList' ]);
                $router->post('files/save', [ 'uses' => 'Server\AjaxController@postSaveFile' ]);
            });
        });
    }

}
