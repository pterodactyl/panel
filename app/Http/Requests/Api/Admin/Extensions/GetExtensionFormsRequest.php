<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Extensions;

use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetExtensionFormsRequest extends AdminApiRequest
{
    /**
     * Root administrators only: listing the forms runs every extension's field authorization.
     */
    public function permissions(): array
    {
        return [];
    }
}
