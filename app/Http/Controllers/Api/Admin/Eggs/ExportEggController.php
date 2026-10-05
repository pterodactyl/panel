<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Eggs;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Eggs\GetEggRequest;
use Pterodactyl\Models\Egg;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Egg Sharing', 'Export, import, and update eggs from share files.')]
class ExportEggController extends AdminApiController
{
    public const array EXPORT_EXAMPLE = [
        'meta' => [
            'version' => Egg::EXPORT_VERSION,
        ],
        'name' => 'Minecraft Java',
        'author' => 'support@example.com',
        'description' => 'Minecraft Java server.',
        'features' => ['eula'],
        'docker_images' => [
            'Java 21' => 'ghcr.io/pterodactyl/yolks:java_21',
        ],
        'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}',
    ];

    /**
     * Export egg configuration.
     */
    #[Endpoint('Export egg', 'Downloads an egg share JSON file.')]
    #[ScribeResponse(self::EXPORT_EXAMPLE, description: 'Egg share file returned.')]
    public function __invoke(GetEggRequest $request, ExportsEggs $eggs, Egg $egg): Response
    {

        $filename = mb_trim(preg_replace('/\W/', '-', Str::kebab($egg->name)), '-');

        return new Response($eggs->export($egg), Response::HTTP_OK, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=egg-'.$filename.'.json',
            'Content-Type' => 'application/json',
        ]);
    }
}
