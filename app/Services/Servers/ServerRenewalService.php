<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ServerRenewalService
{
    private SuspensionService $suspensionService;
    private SettingsRepositoryInterface $settings;

    /**
     * ServerRenewalService constructor.
     */
    public function __construct(
        SuspensionService $suspensionService,
        SettingsRepositoryInterface $settings
    )
    {
        $this->settings = $settings;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Renews a server.
     *
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Throwable
     */
    public function handle(ClientApiRequest $request, Server $server)
    {
        $user = $request->user();
        $cost = $this->settings->get('jeactyl::renewal:cost', 200);

        if ($user->store_balance < $cost) {
            throw new DisplayException('You do not have enough credits to renew your server.');
        };

        try {
            $server->update([
                'renewal' => $server->renewal + 7,
            ]);
            
            $user->update([
                'store_balance' => $user->store_balance - $cost,
            ]);
        } catch (DisplayException $ex) {
            throw new DisplayException('We ran into an error while renewing your server.');
        };

        if ($server->status == 'suspended') {
            $this->suspensionService->toggle($server, 'unsuspend');
        };
    
        return $server->refresh();
    }
}
