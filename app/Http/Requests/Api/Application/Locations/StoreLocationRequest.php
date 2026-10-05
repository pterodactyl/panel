<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Validation\LocationRules;

class StoreLocationRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_LOCATIONS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Rules to validate the request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return LocationRules::rules();
    }

    /**
     * Rename fields to be more clear in error messages.
     */
    public function attributes(): array
    {
        return [
            'long' => 'Location Description',
            'short' => 'Location Identifier',
        ];
    }

    /** @return LocationCreationData */
    public function payload(): array
    {
        return [
            'short' => $this->string('short')->toString(),
            'long' => $this->filled('long') ? $this->string('long')->toString() : null,
        ];
    }
}
