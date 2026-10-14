<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Extensions\Scribe\ResponseFromResourceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Extensions\Scribe\Strategies\UsePterodactylResponseAttributes;
use Pterodactyl\Http\Resources\Api\Application\ServerVariableResource;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

/*
 * #[ResponseFromResource] documents a resource from the first model in the database, with
 * the attribute's includes, object name, status and meta.
 */

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->createServerModel();

    $config = config('scribe');
    $config['examples']['models_source'] = ['databaseFirst'];

    $this->strategy = new class(new DocumentationConfig($config)) extends UsePterodactylResponseAttributes
    {
        /** @return array{status: int, description: string|null, content: string|false} */
        public function render(ResponseFromResource $attribute): array
        {
            return $this->getApiResourceResponse($attribute);
        }
    };
});

test('resources are documented with their includes, object name, status and meta', function (ResponseFromResource $resource, string $expected): void {
    $documented = $this->strategy->render($resource);

    expect($documented['status'])->toBe($resource->status)
        ->and($documented['description'])->toBe($resource->description)
        ->and($documented['content'])->toContain($expected);
})->with([
    'an item with an include, a status and meta' => [
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, status: 201, description: 'Variable created.', resourceKey: 'server_variable', meta: ['resource' => 'https://panel.example.test/api/application/servers/1'], include: ['parent']),
        '"attributes":null}}},"meta":{"resource":"https:\/\/panel.example.test\/api\/application\/servers\/1"}}',
    ],
    'a paginated list with meta after the pagination' => [
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, collection: true, resourceKey: 'server_variable', paginate: 50, meta: ['note' => 'example']),
        '"meta":{"pagination":{"total":1,"count":1,"per_page":50,"current_page":1,"total_pages":1,"links":[]},"note":"example"}',
    ],
    'an item without a resource key' => [
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, include: ['parent']),
        '{"object":null,"attributes":',
    ],
    'a list with counts loaded after the model and a gated include' => [
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, collection: true, resourceKey: 'server_variable', include: ['parent'], withCount: ['serverVariable']),
        '"server_variable_count":0,"relationships":{"parent":{"object":"null_resource","attributes":null}}',
    ],
]);
