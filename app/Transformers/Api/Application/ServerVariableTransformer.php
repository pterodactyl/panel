<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\ServerVariable;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;

class ServerVariableTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var list<string>
     */
    protected array $availableIncludes = ['parent'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server variable array.
     *
     * @return ApiPayload
     */
    public function transform(EggVariable $variable): array
    {
        $attributes = $variable->attributesToArray();
        JsonValueGuard::assertPayload($attributes);

        return $attributes;
    }

    /**
     * Return the parent service variable data.
     *
     * @throws InvalidTransformerLevelException
     */
    public function includeParent(EggVariable $variable): Item|NullResource
    {
        if (! $this->authorize(AdminAcl::RESOURCE_EGGS)) {
            return $this->null();
        }

        return $this->item($variable, $this->makeTransformer(EggVariableTransformer::class), 'variable');
    }
}
