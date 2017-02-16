(function () {

    var laroute = (function () {

        var routes = {

            absolute: false,
            rootUrl: 'http://pterodactyl.app',
            routes : [{"host":null,"methods":["GET","HEAD"],"uri":"admin","name":"admin.index","action":"Pterodactyl\Http\Controllers\Admin\BaseController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/settings","name":"admin.settings","action":"Pterodactyl\Http\Controllers\Admin\BaseController@getSettings"},{"host":null,"methods":["POST"],"uri":"admin\/settings","name":null,"action":"Pterodactyl\Http\Controllers\Admin\BaseController@postSettings"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users","name":"admin.users","action":"Pterodactyl\Http\Controllers\Admin\UserController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users\/accounts.json","name":"admin.users.json","action":"Pterodactyl\Http\Controllers\Admin\UserController@getJson"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users\/view\/{id}","name":"admin.users.view","action":"Pterodactyl\Http\Controllers\Admin\UserController@getView"},{"host":null,"methods":["POST"],"uri":"admin\/users\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\UserController@updateUser"},{"host":null,"methods":["DELETE"],"uri":"admin\/users\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\UserController@deleteUser"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users\/new","name":"admin.users.new","action":"Pterodactyl\Http\Controllers\Admin\UserController@getNew"},{"host":null,"methods":["POST"],"uri":"admin\/users\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\UserController@postNew"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers","name":"admin.servers","action":"Pterodactyl\Http\Controllers\Admin\ServersController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/new","name":"admin.servers.new","action":"Pterodactyl\Http\Controllers\Admin\ServersController@getNew"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postNewServer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new\/get-nodes","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postNewServerGetNodes"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new\/get-ips","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postNewServerGetIps"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new\/service-options","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postNewServerServiceOption"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new\/option-details","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postNewServerOptionDetails"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}","name":"admin.servers.view","action":"Pterodactyl\Http\Controllers\Admin\ServersController@getView"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/database","name":"admin.servers.database","action":"Pterodactyl\Http\Controllers\Admin\ServersController@postDatabase"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/details","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postUpdateServerDetails"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/container","name":"admin.servers.post.container","action":"Pterodactyl\Http\Controllers\Admin\ServersController@postUpdateContainerDetails"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/startup","name":"admin.servers.post.startup","action":"Pterodactyl\Http\Controllers\Admin\ServersController@postUpdateServerStartup"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/rebuild","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postUpdateServerToggleBuild"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/build","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postUpdateServerUpdateBuild"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/suspend","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postSuspendServer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/unsuspend","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postUnsuspendServer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/installed","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@postToggleInstall"},{"host":null,"methods":["DELETE"],"uri":"admin\/servers\/view\/{id}\/{force?}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@deleteServer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/queuedDeletion","name":"admin.servers.post.queuedDeletion","action":"Pterodactyl\Http\Controllers\Admin\ServersController@postQueuedDeletionHandler"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes","name":"admin.nodes","action":"Pterodactyl\Http\Controllers\Admin\NodesController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/new","name":"admin.nodes.new","action":"Pterodactyl\Http\Controllers\Admin\NodesController@getNew"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@postNew"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}","name":"admin.nodes.view","action":"Pterodactyl\Http\Controllers\Admin\NodesController@getView"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@postView"},{"host":null,"methods":["DELETE"],"uri":"admin\/nodes\/view\/{id}\/deallocate\/single\/{allocation}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@deallocateSingle"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/deallocate\/block","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@deallocateBlock"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/alias","name":"admin.nodes.alias","action":"Pterodactyl\Http\Controllers\Admin\NodesController@setAlias"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/allocations.json","name":"admin.nodes.view.allocations","action":"Pterodactyl\Http\Controllers\Admin\NodesController@getAllocationsJson"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/allocations","name":"admin.nodes.post.allocations","action":"Pterodactyl\Http\Controllers\Admin\NodesController@postAllocations"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/deploy","name":"admin.nodes.deply","action":"Pterodactyl\Http\Controllers\Admin\NodesController@getScript"},{"host":null,"methods":["DELETE"],"uri":"admin\/nodes\/view\/{id}","name":"admin.nodes.delete","action":"Pterodactyl\Http\Controllers\Admin\NodesController@deleteNode"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/{id}\/configurationtoken","name":"admin.nodes.configuration-token","action":"Pterodactyl\Http\Controllers\Admin\NodesController@getConfigurationToken"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/locations","name":"admin.locations","action":"Pterodactyl\Http\Controllers\Admin\LocationsController@getIndex"},{"host":null,"methods":["DELETE"],"uri":"admin\/locations\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\LocationsController@deleteLocation"},{"host":null,"methods":["PATCH"],"uri":"admin\/locations\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\LocationsController@patchLocation"},{"host":null,"methods":["POST"],"uri":"admin\/locations","name":null,"action":"Pterodactyl\Http\Controllers\Admin\LocationsController@postLocation"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/databases","name":"admin.databases","action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/databases\/new","name":"admin.databases.new","action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@getNew"},{"host":null,"methods":["POST"],"uri":"admin\/databases\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@postNew"},{"host":null,"methods":["DELETE"],"uri":"admin\/databases\/delete\/{id}","name":"admin.databases.delete","action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@deleteDatabase"},{"host":null,"methods":["DELETE"],"uri":"admin\/databases\/delete-server\/{id}","name":"admin.databases.delete-server","action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@deleteServer"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services","name":"admin.services","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/new","name":"admin.services.new","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@getNew"},{"host":null,"methods":["POST"],"uri":"admin\/services\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postNew"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/service\/{id}","name":"admin.services.service","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@getService"},{"host":null,"methods":["POST"],"uri":"admin\/services\/service\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postService"},{"host":null,"methods":["DELETE"],"uri":"admin\/services\/service\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@deleteService"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/service\/{id}\/configuration","name":"admin.services.service.config","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@getConfiguration"},{"host":null,"methods":["POST"],"uri":"admin\/services\/service\/{id}\/configuration","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postConfiguration"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/service\/{service}\/option\/new","name":"admin.services.option.new","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@newOption"},{"host":null,"methods":["POST"],"uri":"admin\/services\/service\/{service}\/option\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postNewOption"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/service\/{service}\/option\/{option}","name":"admin.services.option","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@getOption"},{"host":null,"methods":["POST"],"uri":"admin\/services\/service\/{service}\/option\/{option}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postOption"},{"host":null,"methods":["DELETE"],"uri":"admin\/services\/service\/{service}\/option\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@deleteOption"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/service\/{service}\/option\/{option}\/variable\/new","name":"admin.services.option.variable.new","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@getNewVariable"},{"host":null,"methods":["POST"],"uri":"admin\/services\/service\/{service}\/option\/{option}\/variable\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postNewVariable"},{"host":null,"methods":["POST"],"uri":"admin\/services\/service\/{service}\/option\/{option}\/variable\/{variable}","name":"admin.services.option.variable","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@postOptionVariable"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/service\/{service}\/option\/{option}\/variable\/{variable}\/delete","name":"admin.services.option.variable.delete","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@deleteVariable"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs\/new\/{option?}","name":"admin.services.packs.new","action":"Pterodactyl\Http\Controllers\Admin\PackController@new"},{"host":null,"methods":["POST"],"uri":"admin\/services\/packs\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\PackController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs\/upload\/{option?}","name":"admin.services.packs.uploadForm","action":"Pterodactyl\Http\Controllers\Admin\PackController@uploadForm"},{"host":null,"methods":["POST"],"uri":"admin\/services\/packs\/upload","name":null,"action":"Pterodactyl\Http\Controllers\Admin\PackController@postUpload"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs","name":"admin.services.packs","action":"Pterodactyl\Http\Controllers\Admin\PackController@listAll"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs\/for\/option\/{option}","name":"admin.services.packs.option","action":"Pterodactyl\Http\Controllers\Admin\PackController@listByOption"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs\/for\/service\/{service}","name":"admin.services.packs.service","action":"Pterodactyl\Http\Controllers\Admin\PackController@listByService"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs\/edit\/{pack}","name":"admin.services.packs.edit","action":"Pterodactyl\Http\Controllers\Admin\PackController@edit"},{"host":null,"methods":["POST"],"uri":"admin\/services\/packs\/edit\/{pack}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\PackController@update"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/packs\/edit\/{pack}\/export\/{archive?}","name":"admin.services.packs.export","action":"Pterodactyl\Http\Controllers\Admin\PackController@export"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/login","name":"auth.login","action":"Pterodactyl\Http\Controllers\Auth\LoginController@showLoginForm"},{"host":null,"methods":["POST"],"uri":"auth\/login","name":null,"action":"Pterodactyl\Http\Controllers\Auth\LoginController@login"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/login\/totp","name":"auth.totp","action":"Pterodactyl\Http\Controllers\Auth\LoginController@totp"},{"host":null,"methods":["POST"],"uri":"auth\/login\/totp","name":null,"action":"Pterodactyl\Http\Controllers\Auth\LoginController@totpCheckpoint"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/password","name":"auth.password","action":"Pterodactyl\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm"},{"host":null,"methods":["POST"],"uri":"auth\/password","name":null,"action":"Pterodactyl\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/password\/reset\/{token}","name":"auth.reset","action":"Pterodactyl\Http\Controllers\Auth\ResetPasswordController@showResetForm"},{"host":null,"methods":["POST"],"uri":"auth\/password\/reset","name":"auth.reset.post","action":"Pterodactyl\Http\Controllers\Auth\ResetPasswordController@reset"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/logout","name":"auth.logout","action":"Pterodactyl\Http\Controllers\Auth\LoginController@logout"},{"host":null,"methods":["GET","HEAD"],"uri":"\/","name":"index","action":"Pterodactyl\Http\Controllers\Base\IndexController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"index","name":null,"action":"Closure"},{"host":null,"methods":["GET","HEAD"],"uri":"password-gen\/{length}","name":"password-gen","action":"Pterodactyl\Http\Controllers\Base\IndexController@getPassword"},{"host":null,"methods":["GET","HEAD"],"uri":"account","name":"account","action":"Pterodactyl\Http\Controllers\Base\AccountController@index"},{"host":null,"methods":["POST"],"uri":"account","name":null,"action":"Pterodactyl\Http\Controllers\Base\AccountController@update"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/api","name":"account.api","action":"Pterodactyl\Http\Controllers\Base\APIController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/api\/new","name":"account.api.new","action":"Pterodactyl\Http\Controllers\Base\APIController@create"},{"host":null,"methods":["POST"],"uri":"account\/api\/new","name":null,"action":"Pterodactyl\Http\Controllers\Base\APIController@save"},{"host":null,"methods":["DELETE"],"uri":"account\/api\/revoke\/{key}","name":"account.api.revoke","action":"Pterodactyl\Http\Controllers\Base\APIController@revoke"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/security","name":"account.security","action":"Pterodactyl\Http\Controllers\Base\SecurityController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/security\/revoke\/{id}","name":"account.security.revoke","action":"Pterodactyl\Http\Controllers\Base\SecurityController@revoke"},{"host":null,"methods":["PUT"],"uri":"account\/security\/totp","name":"account.security.totp","action":"Pterodactyl\Http\Controllers\Base\SecurityController@generateTotp"},{"host":null,"methods":["POST"],"uri":"account\/security\/totp","name":null,"action":"Pterodactyl\Http\Controllers\Base\SecurityController@setTotp"},{"host":null,"methods":["DELETE"],"uri":"account\/security\/totp","name":null,"action":"Pterodactyl\Http\Controllers\Base\SecurityController@disableTotp"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/services","name":"daemon.services","action":"Pterodactyl\Http\Controllers\Daemon\ServiceController@list"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/services\/pull\/{service}\/{file}","name":"remote.install","action":"Pterodactyl\Http\Controllers\Daemon\ServiceController@pull"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/packs\/pull\/{uuid}","name":"daemon.pack.pull","action":"Pterodactyl\Http\Controllers\Daemon\PackController@pull"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/packs\/pull\/{uuid}\/hash","name":"daemon.pack.hash","action":"Pterodactyl\Http\Controllers\Daemon\PackController@hash"},{"host":null,"methods":["GET","HEAD"],"uri":"language\/{lang}","name":"langauge.set","action":"Pterodactyl\Http\Controllers\Base\LanguageController@setLanguage"},{"host":null,"methods":["POST"],"uri":"remote\/download","name":"remote.download","action":"Pterodactyl\Http\Controllers\Remote\RemoteController@postDownload"},{"host":null,"methods":["POST"],"uri":"remote\/install","name":"remote.install","action":"Pterodactyl\Http\Controllers\Remote\RemoteController@postInstall"},{"host":null,"methods":["GET","HEAD"],"uri":"remote\/configuration\/{token}","name":"remote.configuration","action":"Pterodactyl\Http\Controllers\Remote\RemoteController@getConfiguration"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}","name":"server.index","action":"Pterodactyl\Http\Controllers\Server\ServerController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings","name":"server.settings","action":"Pterodactyl\Http\Controllers\Server\ServerController@getSettings"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/databases","name":"server.settings.databases","action":"Pterodactyl\Http\Controllers\Server\ServerController@getDatabases"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/sftp","name":"server.settings.sftp","action":"Pterodactyl\Http\Controllers\Server\ServerController@getSFTP"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/settings\/sftp","name":null,"action":"Pterodactyl\Http\Controllers\Server\ServerController@postSettingsSFTP"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/startup","name":"server.settings.startup","action":"Pterodactyl\Http\Controllers\Server\ServerController@getStartup"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/settings\/startup","name":null,"action":"Pterodactyl\Http\Controllers\Server\ServerController@postSettingsStartup"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/allocation","name":"server.settings.allocation","action":"Pterodactyl\Http\Controllers\Server\ServerController@getAllocation"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files","name":"server.files.index","action":"Pterodactyl\Http\Controllers\Server\ServerController@getFiles"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files\/edit\/{file}","name":"server.files.edit","action":"Pterodactyl\Http\Controllers\Server\ServerController@getEditFile"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files\/download\/{file}","name":"server.files.download","action":"Pterodactyl\Http\Controllers\Server\ServerController@getDownloadFile"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files\/add","name":"server.files.add","action":"Pterodactyl\Http\Controllers\Server\ServerController@getAddFile"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/files\/directory-list","name":"server.files.directory-list","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postDirectoryList"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/files\/save","name":"server.files.save","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postSaveFile"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/users","name":"server.subusers","action":"Pterodactyl\Http\Controllers\Server\SubuserController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/users\/new","name":"server.subusers.new","action":"Pterodactyl\Http\Controllers\Server\SubuserController@getNew"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/users\/new","name":null,"action":"Pterodactyl\Http\Controllers\Server\SubuserController@postNew"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/users\/view\/{id}","name":"server.subusers.view","action":"Pterodactyl\Http\Controllers\Server\SubuserController@getView"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/users\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Server\SubuserController@postView"},{"host":null,"methods":["DELETE"],"uri":"server\/{server}\/users\/delete\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Server\SubuserController@deleteSubuser"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/tasks","name":"server.tasks","action":"Pterodactyl\Http\Controllers\Server\TaskController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/tasks\/view\/{id}","name":"server.tasks.view","action":"Pterodactyl\Http\Controllers\Server\TaskController@getView"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/tasks\/new","name":"server.tasks.new","action":"Pterodactyl\Http\Controllers\Server\TaskController@getNew"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/tasks\/new","name":null,"action":"Pterodactyl\Http\Controllers\Server\TaskController@postNew"},{"host":null,"methods":["DELETE"],"uri":"server\/{server}\/tasks\/delete\/{id}","name":"server.tasks.delete","action":"Pterodactyl\Http\Controllers\Server\TaskController@deleteTask"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/tasks\/toggle\/{id}","name":"server.tasks.toggle","action":"Pterodactyl\Http\Controllers\Server\TaskController@toggleTask"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/ajax\/status","name":"server.ajax.status","action":"Pterodactyl\Http\Controllers\Server\AjaxController@getStatus"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/ajax\/set-primary","name":null,"action":"Pterodactyl\Http\Controllers\Server\AjaxController@postSetPrimary"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/ajax\/settings\/reset-database-password","name":"server.ajax.reset-database-password","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postResetDatabasePassword"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/open","name":"debugbar.openhandler","action":"Barryvdh\Debugbar\Controllers\OpenHandlerController@handle"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/clockwork\/{id}","name":"debugbar.clockwork","action":"Barryvdh\Debugbar\Controllers\OpenHandlerController@clockwork"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/assets\/stylesheets","name":"debugbar.assets.css","action":"Barryvdh\Debugbar\Controllers\AssetController@css"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/assets\/javascript","name":"debugbar.assets.js","action":"Barryvdh\Debugbar\Controllers\AssetController@js"}],
            prefix: '',

            route : function (name, parameters, route) {
                route = route || this.getByName(name);

                if ( ! route ) {
                    return undefined;
                }

                return this.toRoute(route, parameters);
            },

            url: function (url, parameters) {
                parameters = parameters || [];

                var uri = url + '/' + parameters.join('/');

                return this.getCorrectUrl(uri);
            },

            toRoute : function (route, parameters) {
                var uri = this.replaceNamedParameters(route.uri, parameters);
                var qs  = this.getRouteQueryString(parameters);

                return this.getCorrectUrl(uri + qs);
            },

            replaceNamedParameters : function (uri, parameters) {
                uri = uri.replace(/\{(.*?)\??\}/g, function(match, key) {
                    if (parameters.hasOwnProperty(key)) {
                        var value = parameters[key];
                        delete parameters[key];
                        return value;
                    } else {
                        return match;
                    }
                });

                // Strip out any optional parameters that were not given
                uri = uri.replace(/\/\{.*?\?\}/g, '');

                return uri;
            },

            getRouteQueryString : function (parameters) {
                var qs = [];
                for (var key in parameters) {
                    if (parameters.hasOwnProperty(key)) {
                        qs.push(key + '=' + parameters[key]);
                    }
                }

                if (qs.length < 1) {
                    return '';
                }

                return '?' + qs.join('&');
            },

            getByName : function (name) {
                for (var key in this.routes) {
                    if (this.routes.hasOwnProperty(key) && this.routes[key].name === name) {
                        return this.routes[key];
                    }
                }
            },

            getByAction : function(action) {
                for (var key in this.routes) {
                    if (this.routes.hasOwnProperty(key) && this.routes[key].action === action) {
                        return this.routes[key];
                    }
                }
            },

            getCorrectUrl: function (uri) {
                var url = this.prefix + '/' + uri.replace(/^\/?/, '');

                if(!this.absolute)
                    return url;

                return this.rootUrl.replace('/\/?$/', '') + url;
            }
        };

        var getLinkAttributes = function(attributes) {
            if ( ! attributes) {
                return '';
            }

            var attrs = [];
            for (var key in attributes) {
                if (attributes.hasOwnProperty(key)) {
                    attrs.push(key + '="' + attributes[key] + '"');
                }
            }

            return attrs.join(' ');
        };

        var getHtmlLink = function (url, title, attributes) {
            title      = title || url;
            attributes = getLinkAttributes(attributes);

            return '<a href="' + url + '" ' + attributes + '>' + title + '</a>';
        };

        return {
            // Generate a url for a given controller action.
            // Router.action('HomeController@getIndex', [params = {}])
            action : function (name, parameters) {
                parameters = parameters || {};

                return routes.route(name, parameters, routes.getByAction(name));
            },

            // Generate a url for a given named route.
            // Router.route('routeName', [params = {}])
            route : function (route, parameters) {
                parameters = parameters || {};

                return routes.route(route, parameters);
            },

            // Generate a fully qualified URL to the given path.
            // Router.route('url', [params = {}])
            url : function (route, parameters) {
                parameters = parameters || {};

                return routes.url(route, parameters);
            },

            // Generate a html link to the given url.
            // Router.link_to('foo/bar', [title = url], [attributes = {}])
            link_to : function (url, title, attributes) {
                url = this.url(url);

                return getHtmlLink(url, title, attributes);
            },

            // Generate a html link to the given route.
            // Router.link_to_route('route.name', [title=url], [parameters = {}], [attributes = {}])
            link_to_route : function (route, title, parameters, attributes) {
                var url = this.route(route, parameters);

                return getHtmlLink(url, title, attributes);
            },

            // Generate a html link to the given controller action.
            // Router.link_to_action('HomeController@getIndex', [title=url], [parameters = {}], [attributes = {}])
            link_to_action : function(action, title, parameters, attributes) {
                var url = this.action(action, parameters);

                return getHtmlLink(url, title, attributes);
            }

        };

    }).call(this);

    /**
     * Expose the class either via AMD, CommonJS or the global object
     */
    if (typeof define === 'function' && define.amd) {
        define(function () {
            return laroute;
        });
    }
    else if (typeof module === 'object' && module.exports){
        module.exports = laroute;
    }
    else {
        window.Router = laroute;
    }

}).call(this);

