<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use InvalidArgumentException;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Application\BaseTransformer as BaseApplicationTransformer;
use UnexpectedValueException;

abstract class BaseClientTransformer extends BaseApplicationTransformer
{
    /**
     * Return the user model of the user requesting this transformation.
     */
    public function getUser(): User
    {
        return $this->request()->user() ?? throw new UnexpectedValueException('Client API transformers require an authenticated user.');
    }

    /**
     * Determine if the API key loaded onto the transformer has permission
     * to access a different resource. This is used when including other
     * models on a transformation request.
     *
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    protected function authorize(string $ability, ?Server $server = null): bool
    {
        throw_unless($server instanceof Server, InvalidArgumentException::class, 'Expected a server when authorizing client transformer includes.');

        return $this->getUser()->can($ability, [$server]);
    }

    /**
     * @template T of BaseClientTransformer
     *
     * @param  class-string<T>  $abstract
     * @return T
     */
    protected function makeTransformer(string $abstract): self
    {
        throw_unless(is_subclass_of($abstract, self::class), InvalidTransformerLevelException::class, "Transformer [$abstract] must extend ".self::class.'.');

        return parent::makeTransformer($abstract);
    }
}
