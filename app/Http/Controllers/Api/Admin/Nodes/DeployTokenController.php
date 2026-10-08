<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Api\CreatesApiKeys;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\DeployTokenRequest;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes.')]
class DeployTokenController extends AdminApiController
{
    private const array DEPLOY_TOKEN_EXAMPLE = [
        'node' => 1,
        'token' => 'ptla_1234567890abcdef',
        'panel_url' => 'https://panel.example.com',
        'allow_insecure' => false,
    ];

    /**
     * Create node deployment token.
     */
    #[Endpoint('Create node deploy token', 'Creates or reuses an application API key that can deploy the specified node.')]
    #[ScribeResponse(self::DEPLOY_TOKEN_EXAMPLE, description: 'Deploy token returned.')]
    public function __invoke(DeployTokenRequest $request, CreatesApiKeys $keyCreator, Node $node): JsonResponse
    {
        $permissions = [];
        foreach (AdminAcl::getResourceList() as $resource) {
            $permissions[AdminAcl::COLUMN_IDENTIFIER.$resource] = $resource === AdminAcl::RESOURCE_NODES ? AdminAcl::READ | AdminAcl::WRITE : AdminAcl::NONE;
        }

        $key = ApiKey::query()
            ->where('user_id', $request->user()->id)
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where($permissions)
            ->first();

        if (! $key) {
            $key = $keyCreator->create(ApiKey::TYPE_APPLICATION, [
                'user_id' => $request->user()->id,
                'memo' => 'Automatically generated node deployment key.',
                'allowed_ips' => [],
            ], $permissions);
        }

        Activity::event('admin:node.deploy-token')
            ->subject($node)
            ->property('name', $node->name)
            ->log();

        return new JsonResponse([
            'node' => $node->id,
            'token' => $key->identifier.JsonValueGuard::string(Crypt::decrypt($key->token)),
            'panel_url' => config('app.url'),
            'allow_insecure' => config('app.debug'),
        ]);
    }
}
