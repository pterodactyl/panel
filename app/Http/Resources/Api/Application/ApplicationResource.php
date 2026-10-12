<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Application;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;

/**
 * A resource of the Application API, which an application API key reads.
 *
 * @template TResource of Model|ServerState|array<mixed>
 *
 * @extends ApiResource<TResource>
 */
abstract class ApplicationResource extends ApiResource
{
    /** @var string|null */
    public static $wrap;

    /**
     * Determine if the API key behind the request may read another resource, which gates
     * the includes that embed it.
     */
    protected function authorize(string $ability): bool
    {
        $allowed = [ApiKey::TYPE_ACCOUNT, ApiKey::TYPE_APPLICATION];

        $token = $this->request()->user()?->currentAccessToken();
        if (! $token instanceof ApiKey || ! in_array($token->key_type, $allowed)) {
            return false;
        }

        // If this is not a deprecated application token type we can only check that
        // the user is a root admin at the moment. In a future release we'll be rolling
        // out more specific permissions for keys.
        if ($token->key_type === ApiKey::TYPE_ACCOUNT) {
            return $this->request()->user()->root_admin;
        }

        return AdminAcl::check($token, $ability);
    }

    /**
     * The Application API only nests its own resources.
     *
     * @template TNested of ApiResource<*>
     *
     * @param  class-string<TNested>  $resource
     * @param  Model|ServerState|array<mixed>  $data
     * @return TNested
     *
     * @throws InvalidTransformerLevelException
     */
    protected function makeResource(string $resource, Model|ServerState|array $data): ApiResource
    {
        throw_unless(is_subclass_of($resource, self::class), InvalidTransformerLevelException::class, "Resource [$resource] must extend ".self::class.'.');

        return parent::makeResource($resource, $data);
    }
}
