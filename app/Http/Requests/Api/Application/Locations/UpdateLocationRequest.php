<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Pterodactyl\Actions\Locations\CreateLocation;
use Pterodactyl\Models\Location;

class UpdateLocationRequest extends StoreLocationRequest
{
    /**
     * Rules to validate this request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateLocation::rules($this->parameter('location', Location::class));
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Location
    {
        return $this->parameter('location', Location::class);
    }
}
