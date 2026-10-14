<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Pterodactyl\Data\ServerState;
use Pterodactyl\Exceptions\Resource\InvalidResourceLevelException;
use Pterodactyl\Http\Resources\Api\ApiResource;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use UnexpectedValueException;

/**
 * @template TResource of Model|ServerState|array<mixed>
 *
 * @extends ApiResource<TResource>
 */
abstract class ClientResource extends ApiResource
{
    /** @var string|null */
    public static $wrap;

    public function getUser(): User
    {
        return $this->request()->user() ?? throw new UnexpectedValueException('Client API resources require an authenticated user.');
    }

    /**
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    protected function authorize(string $ability, ?Server $server = null): bool
    {
        throw_unless($server instanceof Server, InvalidArgumentException::class, 'Expected a server when authorizing client resource includes.');

        return $this->getUser()->can($ability, [$server]);
    }

    /**
     * @template TNested of ApiResource<*>
     *
     * @param  class-string<TNested>  $resource
     * @param  Model|ServerState|array<mixed>  $data
     * @return TNested
     *
     * @throws InvalidResourceLevelException
     */
    protected function makeResource(string $resource, Model|ServerState|array $data): ApiResource
    {
        throw_unless(is_subclass_of($resource, self::class), InvalidResourceLevelException::class, "Resource [$resource] must extend ".self::class.'.');

        return parent::makeResource($resource, $data);
    }
}
