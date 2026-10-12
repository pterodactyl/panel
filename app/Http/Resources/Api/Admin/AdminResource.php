<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Http\Resources\Api\ApiResource;

/**
 * A resource of the admin API behind the admin panel.
 *
 * @template TResource of Model|ServerState|array<mixed>
 *
 * @extends ApiResource<TResource>
 */
abstract class AdminResource extends ApiResource
{
    /** @var string|null */
    public static $wrap;

    /**
     * The admin API returns every extension's fields; the Application API only those marked
     * #[ApplicationApi].
     */
    protected function exposesAllExtensionFields(): bool
    {
        return true;
    }

    /**
     * The admin API only nests its own resources, so it never leaks Application or Client ones.
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
