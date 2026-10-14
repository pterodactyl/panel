<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Locations;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Actions\Locations\CreateLocation;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Location;

class StoreLocationRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminLocationsCreate];
    }

    /**
     * Validation rules for creating a location.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateLocation::rules();
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'short' => 'Location Identifier',
            'long' => 'Location Description',
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
