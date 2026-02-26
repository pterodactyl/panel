<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\KeyCreationService;

class NodeAutoDeployController extends Controller
{
    /**
     * NodeAutoDeployController constructor.
     */
    public function __construct(
        private Encrypter $encrypter,
        private KeyCreationService $keyCreationService,
    ) {
    }

    /**
     * Generates a new API key for the logged-in user with only permission to read
     * nodes, and returns that as the deployment key for a node.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function __invoke(Request $request, Node $node): JsonResponse
    {
        $key = ApiKey::query()
            ->where('user_id', $request->user()->id)
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('r_nodes', 1)
            ->first();

        // We couldn't find a key that exists for this user with only permission for
        // reading nodes. Go ahead and create it now.
        if (!$key) {
            $key = $this->keyCreationService->setKeyType(ApiKey::TYPE_APPLICATION)->handle([
                'user_id' => $request->user()->id,
                'memo' => 'Automatically generated node deployment key.',
                'allowed_ips' => [],
            ], ['r_nodes' => 1]);
        }

        return new JsonResponse([
            'node' => $node->id,
            'token' => $key->identifier . $this->encrypter->decrypt($key->token),
        ]);
    }
}
