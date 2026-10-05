<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class ImportEggRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsCreate];
    }

    /**
     * Validation rules for importing a new egg from an uploaded JSON file.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'import_file' => ['bail', 'required', 'file', 'max:1000', 'mimetypes:application/json,text/plain'],
        ];
    }
}
