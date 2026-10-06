<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Extensions;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\GetExtensionFormValuesRequest;
use Pterodactyl\Services\Extensions\ExtensionFormFields;
use Pterodactyl\Support\JsonEmptyObject;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Extensions', 'Install, inspect, enable, disable, and remove panel extensions.')]
class ExtensionFormValuesController extends AdminApiController
{
    #[Endpoint('Get extension form values', 'Returns the current values of the fields extensions have added to a resource form, keyed by extension id.')]
    #[ScribeResponse(['data' => ['billing' => ['plan' => 'gold']]], description: 'Extension form values returned.')]
    public function __invoke(GetExtensionFormValuesRequest $request, ExtensionFormFields $fields): JsonResponse
    {
        $values = array_map(
            fn (array $value): array|JsonEmptyObject => $value === [] ? new JsonEmptyObject : $value,
            $fields->values($request->form(), $request->subject()),
        );

        return new JsonResponse(['data' => $values === [] ? new JsonEmptyObject : $values]);
    }
}
