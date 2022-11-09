<?php

namespace Pterodactyl\Services\Store;

use Throwable;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Store\CreateServerRequest;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class StoreCreationService
{
    public function __construct(
        private ServerCreationService $creation,
        private SettingsRepositoryInterface $settings,
        private StoreVerificationService $verification
    ) {
    }

    /**
     * Creates a server on Jexactyl using the Storefront.
     *
     * @throws DisplayException
     */
    public function handle(CreateServerRequest $request): Server
    {
        $this->verification->handle($request);

        $user = User::find($request->user()->id);
        $egg = Egg::find($request->input('egg'));

        $nest = Nest::find($request->input('nest'));
        $node = Node::find($request->input('node'));

        $disk = $request->input('disk');
        $memory = $request->input('memory');

        $data = [
            'name' => $request->input('name'),
            'owner_id' => $user->id,
            'egg_id' => $egg->id,
            'nest_id' => $nest->id,
            'node_id' => $node->id,
            'allocation_id' => $this->getAllocation($node->id),
            'allocation_limit' => $request->input('ports'),
            'backup_limit' => $request->input('backups'),
            'database_limit' => $request->input('databases'),
            'environment' => [],
            'memory' => $memory,
            'disk' => $disk,
            'cpu' => $request->input('cpu'),
            'swap' => 0,
            'io' => 500,
            'image' => array_values($egg->docker_images)[0],
            'startup' => $egg->startup,
            'start_on_completion' => false,
            // Settings for the renewal system. Even if the renewal system is disabled,
            // mark this server as enabled - so that if the renewal system is enabled again,
            // it'll be part of the renewable servers.
            'renewable' => true,
            'renewal' => $this->settings->get('jexactyl::renewal:default'),
        ];

        foreach (EggVariable::where('egg_id', $egg->id)->get() as $var) {
            $key = "v1-{$egg->id}-{$var->env_variable}";
            $data['environment'][$var->env_variable] = $request->get($key, $var->default_value);
        }

        try {
            $server = $this->creation->handle($data);
        } catch (Throwable $exception) {
            throw new DisplayException('Unable to deploy server - Please contact an administrator.');
        }

        return $server;
    }

    /**
     * Gets an allocation for server deployment.
     *
     * @throws NoViableAllocationException
     */
    protected function getAllocatiom(int $node): int
    {
        $allocation = Allocation::where('node_id', $node)
            ->where('server_id', null)
            ->first();

        if (!$allocation) {
            throw new NoViableAllocationException('No allocations are available for deployment.');
        }

        return $allocation->id;
    }
}
