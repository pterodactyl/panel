<?php

namespace Pterodactyl\Services\Store;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Store\StoreVerificationService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Store\CreateServerRequest;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class StoreCreationService {

    private ServerCreationService $creation;
    private SettingsRepositoryInterface $settings;
    private StoreVerificationService $verification;

    public function __construct(
        ServerCreationService $creation,
        SettingsRepositoryInterface $settings,
        StoreVerificationService $verification
    )
    {
        $this->creation = $creation;
        $this->settings = $settings;
        $this->verification = $verification;
    }

    /**
     * Creates a server on Jexactyl using the Storefront.
     */
    public function handle(CreateServerRequest $request): JsonResponse
    {
        $this->verification->handle($request);
        
        $user = User::find($request->user()->id);
        $egg = Egg::find($request->input('egg'));

        $nest = Nest::find($request->input('nest'));
        $node = Node::find($request->input('node'));

        $disk = $request->input('disk') * 1024;
        $memory = $request->input('memory') * 1024;

        $data = [
            'name' => $request->input('name'),
            'owner_id' => $user->id,
            'egg_id' => $egg->id,
            'nest_id' => $nest->id,
            'node_id' => $node->id,
            'allocation_id' => $this->getAlloc($node->id),
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
            $this->creation->handle($data);
        } catch (DisplayException $exception) {
            throw new DisplayException('Unable to deploy server - Please contact an administrator.');
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Gets an allocation for server deployment.
     *
     * @throws NoViableAllocationException
     */
    protected function getAlloc(int $node): int
    {
        $allocation = Allocation::where('node_id', $node)
            ->where('server_id', null)
            ->first();

        if (!$allocation) {
            throw new NoViableAllocationException('No allocations are available for deployment.');
        };

        return $allocation->id;
    }
}
