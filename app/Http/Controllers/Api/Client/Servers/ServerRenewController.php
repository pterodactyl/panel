<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Services\Servers\SuspensionService;

class ServerRenewController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    protected $settingsRepository;

    /**
     * @var SuspensionService
     */
    protected $suspensionService;

    /**
     * @param SettingsRepositoryInterface $settingsRepository
     * @param SuspensionService $suspensionService
     */
    public function __construct(SettingsRepositoryInterface $settingsRepository, SuspensionService $suspensionService)
    {
        parent::__construct();

        $this->settingsRepository = $settingsRepository;
        $this->suspensionService = $suspensionService;
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     */
    public function index(Request $request, Server $server)
    {
        if (Auth::user()->id !== $server->owner_id && !Auth::user()->root_admin) {
            if (!Server::find($server->id)->subusers->contains('user_id', Auth::user()->id)) {
                throw new DisplayException('You don\'t have an access to this action.');
            }
        }

        $price = 0;

        if (!is_null($server->product_id)) {
            $game = DB::table('games')->where('id', '=', $server->product_id)->get();
            if (count($game) < 1) {
                throw new DisplayException('Game package not found.');
            }

            $price = $game[0]->price;
        }

        return [
            'success' => true,
            'data' => [
                'price' => $price,
                'expired_at' => $server->expired_at,
                'currency' => $this->settingsRepository->get('settings::shop::currency', 'USD'),
                'balance' => Auth::user()->credit,
            ],
        ];
    }

    /**
     * @param Request $request
     * @param Server $server
     * @return array
     * @throws DisplayException
     */
    public function renew(Request $request, Server $server)
    {
        if (Auth::user()->id !== $server->owner_id && !Auth::user()->root_admin) {
            if (!Server::find($server->id)->subusers->contains('user_id', Auth::user()->id)) {
                throw new DisplayException('You don\'t have an access to this action.');
            }
        }

        if (is_null($server->product_id)) {
            throw new DisplayException('You can\'t renew this server.');
        }

        $game = DB::table('games')->where('id', '=', $server->product_id)->get();
        if (count($game) < 1) {
            throw new DisplayException('Game package not found.');
        }

        if (Auth::user()->credit < $game[0]->price) {
            throw new DisplayException('You don\'t have enought balance to renew this server.');
        }

        DB::table('users')->where('id', '=', Auth::user()->id)->update([
            'credit' => Auth::user()->credit - $game[0]->price,
        ]);

        DB::table('servers')->where('id', '=', $server->id)->update([
            'expired_at' => Carbon::parse($server->expired_at)->addDays(30),
        ]);

        if ($server->status == 'suspended') {
            try {
                $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND);
            } catch (\Throwable $e) {
                throw new DisplayException('Please contact us, because the renew was successful, but failed to unsuspend the server.');
            }
        }

        return [
            'success' => true,
            'data' => [],
        ];
    }
}
