<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application;

use Laravel\Sanctum\TransientToken;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Http\Requests\Api\ApiRequest;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;

abstract class ApplicationApiRequest extends ApiRequest
{
    /**
     * The resource that should be checked when performing the authorization
     * function for this request.
     */
    protected ?string $resource = null;

    /**
     * The permission level that a given API key should have for accessing
     * the defined $resource during the request cycle.
     */
    protected int $permission = AdminAcl::NONE;

    /**
     * Determine if the current user is authorized to perform
     * the requested action against the API.
     *
     * @throws PterodactylException
     */
    public function authorize(): bool
    {
        throw_if(($this->resource) === null, PterodactylException::class, 'An ACL resource must be defined on API requests.');

        $user = $this->user();
        throw_if($user === null, PterodactylException::class, 'Application API requests require an authenticated user.');

        $token = $user->currentAccessToken();
        if ($token === null || $token instanceof TransientToken) {
            return true;
        }

        if ($token->key_type === ApiKey::TYPE_ACCOUNT) {
            return true;
        }

        return AdminAcl::check($token, $this->resource, $this->permission);
    }
}
