<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;

abstract class BaseAdminTransformer extends BaseTransformer
{
    /**
     * Assert child transformers are also admin transformers so the admin API never leaks application/client ones.
     *
     * @template T of BaseAdminTransformer
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
