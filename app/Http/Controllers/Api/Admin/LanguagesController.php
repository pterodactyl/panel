<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Requests\Api\Admin\GetLanguagesRequest;
use Pterodactyl\Traits\Helpers\AvailableLanguages;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('System', 'Endpoints for system metadata and global admin utilities.')]
class LanguagesController extends AdminApiController
{
    use AvailableLanguages;

    private const array LANGUAGES_EXAMPLE = [
        'en' => 'English',
    ];

    /**
     * Return languages available for Panel locale selectors.
     */
    #[Endpoint('List languages', 'Returns the languages available for panel locale selectors.')]
    #[ScribeResponse(self::LANGUAGES_EXAMPLE, description: 'Available languages returned.')]
    public function __invoke(GetLanguagesRequest $request): JsonResponse
    {
        return new JsonResponse($this->getAvailableLanguages(true));
    }
}
