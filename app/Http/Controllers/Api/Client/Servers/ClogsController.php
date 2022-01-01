<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\Carbon;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Http\Requests\Api\Client\Servers\ClogsRequest;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;

class ClogsController extends ClientApiController
{ 
    public function index(ClogsRequest $request, Server $server): array
    {
        $allRequest = DB::table('audit_logs')->get();
        $requests = DB::table('audit_logs')->where('server_id', '=', $server->id)->get();
        foreach ($requests as $key => $requests) {
        $requests = DB::table('audit_logs')->where('server_id', '=', $server->id)->get();
}
        return [
            'success' => true,
            'data' => [
                'requests' => $requests,
            ],
        ];
    }
}
