<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Actions\Locations\CreateLocation;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class StoreLocationRequest extends ApplicationApiRequest
{
    use ValidatesExtensionFields;

    protected ?string $resource = AdminAcl::RESOURCE_LOCATIONS;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Rules to validate the request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateLocation::rules();
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

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return Location::class;
    }
}
