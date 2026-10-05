<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class ImportCatalogEggRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsCreate];
    }

    /** @return ValidationRules */
    public function rules(): array
    {
        return [
            'catalog_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function catalogId(): string
    {
        return $this->string('catalog_id')->toString();
    }
}
