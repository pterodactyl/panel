(function () {

    var laroute = (function () {

        var routes = {

            absolute: false,
            rootUrl: 'http://pterodactyl.app',
            routes : [{"host":null,"methods":["GET","HEAD"],"uri":"api\/user","name":"api.user","action":"Pterodactyl\Http\Controllers\API\User\CoreController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/user\/server\/{server}","name":"api.user.server","action":"Pterodactyl\Http\Controllers\API\User\ServerController@index"},{"host":null,"methods":["POST"],"uri":"api\/user\/server\/{server}\/power","name":"api.user.server.power","action":"Pterodactyl\Http\Controllers\API\User\ServerController@power"},{"host":null,"methods":["POST"],"uri":"api\/user\/server\/{server}\/command","name":"api.user.server.command","action":"Pterodactyl\Http\Controllers\API\User\ServerController@command"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\CoreController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/servers","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/servers\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@view"},{"host":null,"methods":["POST"],"uri":"api\/admin\/servers","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@store"},{"host":null,"methods":["PUT"],"uri":"api\/admin\/servers\/{id}\/details","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@details"},{"host":null,"methods":["PUT"],"uri":"api\/admin\/servers\/{id}\/container","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@container"},{"host":null,"methods":["PUT"],"uri":"api\/admin\/servers\/{id}\/build","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@build"},{"host":null,"methods":["PUT"],"uri":"api\/admin\/servers\/{id}\/startup","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@startup"},{"host":null,"methods":["PATCH"],"uri":"api\/admin\/servers\/{id}\/install","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@install"},{"host":null,"methods":["PATCH"],"uri":"api\/admin\/servers\/{id}\/rebuild","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@rebuild"},{"host":null,"methods":["PATCH"],"uri":"api\/admin\/servers\/{id}\/suspend","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@suspend"},{"host":null,"methods":["DELETE"],"uri":"api\/admin\/servers\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServerController@delete"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/locations","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\LocationController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/nodes","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\NodeController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/nodes\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\NodeController@view"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/nodes\/{id}\/config","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\NodeController@viewConfig"},{"host":null,"methods":["POST"],"uri":"api\/admin\/nodes","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\NodeController@store"},{"host":null,"methods":["DELETE"],"uri":"api\/admin\/nodes\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\NodeController@delete"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/users","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\UserController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/users\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\UserController@view"},{"host":null,"methods":["POST"],"uri":"api\/admin\/users","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\UserController@store"},{"host":null,"methods":["PUT"],"uri":"api\/admin\/users\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\UserController@update"},{"host":null,"methods":["DELETE"],"uri":"api\/admin\/users\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\UserController@delete"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/services","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServiceController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/admin\/services\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\API\Admin\ServiceController@view"},{"host":null,"methods":["GET","HEAD"],"uri":"\/","name":"index","action":"Pterodactyl\Http\Controllers\Base\IndexController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"status\/{server}","name":"index.status","action":"Pterodactyl\Http\Controllers\Base\IndexController@status"},{"host":null,"methods":["GET","HEAD"],"uri":"index","name":null,"action":"Closure"},{"host":null,"methods":["GET","HEAD"],"uri":"account","name":"account","action":"Pterodactyl\Http\Controllers\Base\AccountController@index"},{"host":null,"methods":["POST"],"uri":"account","name":null,"action":"Pterodactyl\Http\Controllers\Base\AccountController@update"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/api","name":"account.api","action":"Pterodactyl\Http\Controllers\Base\APIController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/api\/new","name":"account.api.new","action":"Pterodactyl\Http\Controllers\Base\APIController@create"},{"host":null,"methods":["POST"],"uri":"account\/api\/new","name":null,"action":"Pterodactyl\Http\Controllers\Base\APIController@store"},{"host":null,"methods":["DELETE"],"uri":"account\/api\/revoke\/{key}","name":"account.api.revoke","action":"Pterodactyl\Http\Controllers\Base\APIController@revoke"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/security","name":"account.security","action":"Pterodactyl\Http\Controllers\Base\SecurityController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"account\/security\/revoke\/{id}","name":"account.security.revoke","action":"Pterodactyl\Http\Controllers\Base\SecurityController@revoke"},{"host":null,"methods":["PUT"],"uri":"account\/security\/totp","name":"account.security.totp","action":"Pterodactyl\Http\Controllers\Base\SecurityController@generateTotp"},{"host":null,"methods":["POST"],"uri":"account\/security\/totp","name":null,"action":"Pterodactyl\Http\Controllers\Base\SecurityController@setTotp"},{"host":null,"methods":["DELETE"],"uri":"account\/security\/totp","name":null,"action":"Pterodactyl\Http\Controllers\Base\SecurityController@disableTotp"},{"host":null,"methods":["GET","HEAD"],"uri":"admin","name":"admin.index","action":"Pterodactyl\Http\Controllers\Admin\BaseController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/locations","name":"admin.locations","action":"Pterodactyl\Http\Controllers\Admin\LocationController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/locations\/view\/{id}","name":"admin.locations.view","action":"Pterodactyl\Http\Controllers\Admin\LocationController@view"},{"host":null,"methods":["POST"],"uri":"admin\/locations","name":null,"action":"Pterodactyl\Http\Controllers\Admin\LocationController@create"},{"host":null,"methods":["POST"],"uri":"admin\/locations\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\LocationController@update"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/databases","name":"admin.databases","action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/databases\/view\/{id}","name":"admin.databases.view","action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@view"},{"host":null,"methods":["POST"],"uri":"admin\/databases","name":null,"action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@create"},{"host":null,"methods":["POST"],"uri":"admin\/databases\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\DatabaseController@update"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/settings","name":"admin.settings","action":"Pterodactyl\Http\Controllers\Admin\BaseController@getSettings"},{"host":null,"methods":["POST"],"uri":"admin\/settings","name":null,"action":"Pterodactyl\Http\Controllers\Admin\BaseController@postSettings"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users","name":"admin.users","action":"Pterodactyl\Http\Controllers\Admin\UserController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users\/accounts.json","name":"admin.users.json","action":"Pterodactyl\Http\Controllers\Admin\UserController@json"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users\/new","name":"admin.users.new","action":"Pterodactyl\Http\Controllers\Admin\UserController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/users\/view\/{id}","name":"admin.users.view","action":"Pterodactyl\Http\Controllers\Admin\UserController@view"},{"host":null,"methods":["POST"],"uri":"admin\/users\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\UserController@store"},{"host":null,"methods":["POST"],"uri":"admin\/users\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\UserController@update"},{"host":null,"methods":["DELETE"],"uri":"admin\/users\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\UserController@delete"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers","name":"admin.servers","action":"Pterodactyl\Http\Controllers\Admin\ServersController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/new","name":"admin.servers.new","action":"Pterodactyl\Http\Controllers\Admin\ServersController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}","name":"admin.servers.view","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}\/details","name":"admin.servers.view.details","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewDetails"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}\/build","name":"admin.servers.view.build","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewBuild"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}\/startup","name":"admin.servers.view.startup","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewStartup"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}\/database","name":"admin.servers.view.database","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewDatabase"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}\/manage","name":"admin.servers.view.manage","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewManage"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/servers\/view\/{id}\/delete","name":"admin.servers.view.delete","action":"Pterodactyl\Http\Controllers\Admin\ServersController@viewDelete"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@store"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/new\/nodes","name":"admin.servers.new.nodes","action":"Pterodactyl\Http\Controllers\Admin\ServersController@nodes"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/details","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@setDetails"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/details\/container","name":"admin.servers.view.details.container","action":"Pterodactyl\Http\Controllers\Admin\ServersController@setContainer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/build","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@updateBuild"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/startup","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@saveStartup"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/database","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@newDatabase"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/manage\/toggle","name":"admin.servers.view.manage.toggle","action":"Pterodactyl\Http\Controllers\Admin\ServersController@toggleInstall"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/manage\/rebuild","name":"admin.servers.view.manage.rebuild","action":"Pterodactyl\Http\Controllers\Admin\ServersController@rebuildContainer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/manage\/suspension","name":"admin.servers.view.manage.suspension","action":"Pterodactyl\Http\Controllers\Admin\ServersController@manageSuspension"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/manage\/reinstall","name":"admin.servers.view.manage.reinstall","action":"Pterodactyl\Http\Controllers\Admin\ServersController@reinstallServer"},{"host":null,"methods":["POST"],"uri":"admin\/servers\/view\/{id}\/delete","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@delete"},{"host":null,"methods":["PATCH"],"uri":"admin\/servers\/view\/{id}\/database","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServersController@resetDatabasePassword"},{"host":null,"methods":["DELETE"],"uri":"admin\/servers\/view\/{id}\/database\/{database}\/delete","name":"admin.servers.view.database.delete","action":"Pterodactyl\Http\Controllers\Admin\ServersController@deleteDatabase"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes","name":"admin.nodes","action":"Pterodactyl\Http\Controllers\Admin\NodesController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/new","name":"admin.nodes.new","action":"Pterodactyl\Http\Controllers\Admin\NodesController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}","name":"admin.nodes.view","action":"Pterodactyl\Http\Controllers\Admin\NodesController@viewIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/settings","name":"admin.nodes.view.settings","action":"Pterodactyl\Http\Controllers\Admin\NodesController@viewSettings"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/configuration","name":"admin.nodes.view.configuration","action":"Pterodactyl\Http\Controllers\Admin\NodesController@viewConfiguration"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/allocation","name":"admin.nodes.view.allocation","action":"Pterodactyl\Http\Controllers\Admin\NodesController@viewAllocation"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/servers","name":"admin.nodes.view.servers","action":"Pterodactyl\Http\Controllers\Admin\NodesController@viewServers"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/nodes\/view\/{id}\/settings\/token","name":"admin.nodes.view.configuration.token","action":"Pterodactyl\Http\Controllers\Admin\NodesController@setToken"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@store"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/settings","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@updateSettings"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/allocation","name":null,"action":"Pterodactyl\Http\Controllers\Admin\NodesController@createAllocation"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/allocation\/remove","name":"admin.nodes.view.allocation.removeBlock","action":"Pterodactyl\Http\Controllers\Admin\NodesController@allocationRemoveBlock"},{"host":null,"methods":["POST"],"uri":"admin\/nodes\/view\/{id}\/allocation\/alias","name":"admin.nodes.view.allocation.setAlias","action":"Pterodactyl\Http\Controllers\Admin\NodesController@allocationSetAlias"},{"host":null,"methods":["DELETE"],"uri":"admin\/nodes\/view\/{id}\/delete","name":"admin.nodes.view.delete","action":"Pterodactyl\Http\Controllers\Admin\NodesController@delete"},{"host":null,"methods":["DELETE"],"uri":"admin\/nodes\/view\/{id}\/allocation\/remove\/{allocation}","name":"admin.nodes.view.allocation.removeSingle","action":"Pterodactyl\Http\Controllers\Admin\NodesController@allocationRemoveSingle"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services","name":"admin.services","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/new","name":"admin.services.new","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/view\/{id}","name":"admin.services.view","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@view"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/view\/{id}\/functions","name":"admin.services.view.functions","action":"Pterodactyl\Http\Controllers\Admin\ServiceController@viewFunctions"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/option\/new","name":"admin.services.option.new","action":"Pterodactyl\Http\Controllers\Admin\OptionController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/option\/{id}","name":"admin.services.option.view","action":"Pterodactyl\Http\Controllers\Admin\OptionController@viewConfiguration"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/option\/{id}\/variables","name":"admin.services.option.variables","action":"Pterodactyl\Http\Controllers\Admin\OptionController@viewVariables"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/services\/option\/{id}\/scripts","name":"admin.services.option.scripts","action":"Pterodactyl\Http\Controllers\Admin\OptionController@viewScripts"},{"host":null,"methods":["POST"],"uri":"admin\/services\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@store"},{"host":null,"methods":["POST"],"uri":"admin\/services\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@edit"},{"host":null,"methods":["POST"],"uri":"admin\/services\/option\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\OptionController@store"},{"host":null,"methods":["POST"],"uri":"admin\/services\/option\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\OptionController@editConfiguration"},{"host":null,"methods":["POST"],"uri":"admin\/services\/option\/{id}\/scripts","name":null,"action":"Pterodactyl\Http\Controllers\Admin\OptionController@updateScripts"},{"host":null,"methods":["POST"],"uri":"admin\/services\/option\/{id}\/variables","name":null,"action":"Pterodactyl\Http\Controllers\Admin\OptionController@createVariable"},{"host":null,"methods":["POST"],"uri":"admin\/services\/option\/{id}\/variables\/{variable}","name":"admin.services.option.variables.edit","action":"Pterodactyl\Http\Controllers\Admin\OptionController@editVariable"},{"host":null,"methods":["DELETE"],"uri":"admin\/services\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\ServiceController@delete"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/packs","name":"admin.packs","action":"Pterodactyl\Http\Controllers\Admin\PackController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/packs\/new","name":"admin.packs.new","action":"Pterodactyl\Http\Controllers\Admin\PackController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/packs\/new\/template","name":"admin.packs.new.template","action":"Pterodactyl\Http\Controllers\Admin\PackController@newTemplate"},{"host":null,"methods":["GET","HEAD"],"uri":"admin\/packs\/view\/{id}","name":"admin.packs.view","action":"Pterodactyl\Http\Controllers\Admin\PackController@view"},{"host":null,"methods":["POST"],"uri":"admin\/packs\/new","name":null,"action":"Pterodactyl\Http\Controllers\Admin\PackController@store"},{"host":null,"methods":["POST"],"uri":"admin\/packs\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Admin\PackController@update"},{"host":null,"methods":["POST"],"uri":"admin\/packs\/view\/{id}\/export\/{files?}","name":"admin.packs.view.export","action":"Pterodactyl\Http\Controllers\Admin\PackController@export"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/logout","name":"auth.logout","action":"Pterodactyl\Http\Controllers\Auth\LoginController@logout"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/login","name":"auth.login","action":"Pterodactyl\Http\Controllers\Auth\LoginController@showLoginForm"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/login\/totp","name":"auth.totp","action":"Pterodactyl\Http\Controllers\Auth\LoginController@totp"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/password","name":"auth.password","action":"Pterodactyl\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm"},{"host":null,"methods":["GET","HEAD"],"uri":"auth\/password\/reset\/{token}","name":"auth.reset","action":"Pterodactyl\Http\Controllers\Auth\ResetPasswordController@showResetForm"},{"host":null,"methods":["POST"],"uri":"auth\/login","name":null,"action":"Pterodactyl\Http\Controllers\Auth\LoginController@login"},{"host":null,"methods":["POST"],"uri":"auth\/login\/totp","name":null,"action":"Pterodactyl\Http\Controllers\Auth\LoginController@totpCheckpoint"},{"host":null,"methods":["POST"],"uri":"auth\/password","name":null,"action":"Pterodactyl\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail"},{"host":null,"methods":["POST"],"uri":"auth\/password\/reset","name":"auth.reset.post","action":"Pterodactyl\Http\Controllers\Auth\ResetPasswordController@reset"},{"host":null,"methods":["POST"],"uri":"auth\/password\/reset\/{token}","name":null,"action":"Pterodactyl\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}","name":"server.index","action":"Pterodactyl\Http\Controllers\Server\ServerController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/console","name":"server.console","action":"Pterodactyl\Http\Controllers\Server\ServerController@getConsole"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/databases","name":"server.settings.databases","action":"Pterodactyl\Http\Controllers\Server\ServerController@getDatabases"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/sftp","name":"server.settings.sftp","action":"Pterodactyl\Http\Controllers\Server\ServerController@getSFTP"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/startup","name":"server.settings.startup","action":"Pterodactyl\Http\Controllers\Server\ServerController@getStartup"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/settings\/allocation","name":"server.settings.allocation","action":"Pterodactyl\Http\Controllers\Server\ServerController@getAllocation"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/settings\/sftp","name":null,"action":"Pterodactyl\Http\Controllers\Server\ServerController@postSettingsSFTP"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/settings\/startup","name":null,"action":"Pterodactyl\Http\Controllers\Server\ServerController@postSettingsStartup"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files","name":"server.files.index","action":"Pterodactyl\Http\Controllers\Server\ServerController@getFiles"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files\/add","name":"server.files.add","action":"Pterodactyl\Http\Controllers\Server\ServerController@getAddFile"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files\/edit\/{file}","name":"server.files.edit","action":"Pterodactyl\Http\Controllers\Server\ServerController@getEditFile"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/files\/download\/{file}","name":"server.files.edit","action":"Pterodactyl\Http\Controllers\Server\ServerController@getDownloadFile"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/files\/directory-list","name":"server.files.directory-list","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postDirectoryList"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/files\/save","name":"server.files.save","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postSaveFile"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/users","name":"server.subusers","action":"Pterodactyl\Http\Controllers\Server\SubuserController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/users\/new","name":"server.subusers.new","action":"Pterodactyl\Http\Controllers\Server\SubuserController@create"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/users\/view\/{id}","name":"server.subusers.view","action":"Pterodactyl\Http\Controllers\Server\SubuserController@view"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/users\/new","name":null,"action":"Pterodactyl\Http\Controllers\Server\SubuserController@store"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/users\/view\/{id}","name":null,"action":"Pterodactyl\Http\Controllers\Server\SubuserController@update"},{"host":null,"methods":["DELETE"],"uri":"server\/{server}\/users\/delete\/{id}","name":"server.subusers.delete","action":"Pterodactyl\Http\Controllers\Server\SubuserController@delete"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/tasks","name":"server.tasks","action":"Pterodactyl\Http\Controllers\Server\TaskController@index"},{"host":null,"methods":["GET","HEAD"],"uri":"server\/{server}\/tasks\/new","name":"server.tasks.new","action":"Pterodactyl\Http\Controllers\Server\TaskController@create"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/tasks\/new","name":null,"action":"Pterodactyl\Http\Controllers\Server\TaskController@store"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/tasks\/toggle\/{id}","name":"server.tasks.toggle","action":"Pterodactyl\Http\Controllers\Server\TaskController@toggle"},{"host":null,"methods":["DELETE"],"uri":"server\/{server}\/tasks\/delete\/{id}","name":"server.tasks.delete","action":"Pterodactyl\Http\Controllers\Server\TaskController@delete"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/ajax\/set-primary","name":"server.ajax.set-primary","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postSetPrimary"},{"host":null,"methods":["POST"],"uri":"server\/{server}\/ajax\/settings\/reset-database-password","name":"server.ajax.reset-database-password","action":"Pterodactyl\Http\Controllers\Server\AjaxController@postResetDatabasePassword"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/services","name":"daemon.services","action":"Pterodactyl\Http\Controllers\Daemon\ServiceController@listServices"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/services\/pull\/{service}\/{file}","name":"daemon.pull","action":"Pterodactyl\Http\Controllers\Daemon\ServiceController@pull"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/packs\/pull\/{uuid}","name":"daemon.pack.pull","action":"Pterodactyl\Http\Controllers\Daemon\PackController@pull"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/packs\/pull\/{uuid}\/hash","name":"daemon.pack.hash","action":"Pterodactyl\Http\Controllers\Daemon\PackController@hash"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/details\/option\/{server}","name":"daemon.option.details","action":"Pterodactyl\Http\Controllers\Daemon\OptionController@details"},{"host":null,"methods":["GET","HEAD"],"uri":"daemon\/configure\/{token}","name":"daemon.configuration","action":"Pterodactyl\Http\Controllers\Daemon\ActionController@configuration"},{"host":null,"methods":["POST"],"uri":"daemon\/download","name":"daemon.download","action":"Pterodactyl\Http\Controllers\Daemon\ActionController@authenticateDownload"},{"host":null,"methods":["POST"],"uri":"daemon\/install","name":"daemon.install","action":"Pterodactyl\Http\Controllers\Daemon\ActionController@markInstall"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/open","name":"debugbar.openhandler","action":"Barryvdh\Debugbar\Controllers\OpenHandlerController@handle"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/clockwork\/{id}","name":"debugbar.clockwork","action":"Barryvdh\Debugbar\Controllers\OpenHandlerController@clockwork"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/assets\/stylesheets","name":"debugbar.assets.css","action":"Barryvdh\Debugbar\Controllers\AssetController@css"},{"host":null,"methods":["GET","HEAD"],"uri":"_debugbar\/assets\/javascript","name":"debugbar.assets.js","action":"Barryvdh\Debugbar\Controllers\AssetController@js"}],
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

                if (this.absolute && this.isOtherHost(route)){
                    return "//" + route.host + "/" + uri + qs;
                }

                return this.getCorrectUrl(uri + qs);
            },

            isOtherHost: function (route){
                return route.host && route.host != window.location.hostname;
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

                if ( ! this.absolute) {
                    return url;
                }

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

