<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * Reading a node's configuration returns its daemon token, so it needs write access to nodes.
 */
class GetNodeConfigurationRequest extends GetNodesRequest
{
    protected int $permission = AdminAcl::WRITE;

    public function authorize(): bool
    {
        $token = $this->user()?->currentAccessToken();

        return ! ($token instanceof ApiKey && $token->key_type === ApiKey::TYPE_ACCOUNT) && parent::authorize();
    }
}
