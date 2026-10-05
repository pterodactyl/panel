<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin;

use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Transformers\Api\Admin\BaseAdminTransformer;

abstract class AdminApiController extends ApplicationApiController
{
    /**
     * Resolve admin API transformer.
     *
     * @template T of BaseAdminTransformer
     *
     * @param  class-string<T>  $abstract
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function getTransformer(string $abstract): BaseAdminTransformer
    {
        return $this->makeTransformer($abstract, BaseAdminTransformer::class);
    }
}
