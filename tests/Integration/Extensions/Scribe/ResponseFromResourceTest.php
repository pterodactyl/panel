<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Extensions\Scribe\ResponseFromResourceTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Knuckles\Scribe\Tools\DocumentationConfig;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Extensions\Scribe\Strategies\UsePterodactylResponseAttributes;
use Pterodactyl\Http\Resources\Api\Application\ServerVariableResource;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Transformers\Api\Application\ServerVariableTransformer;

/*
 * #[ResponseFromResource] must document a resource exactly as #[ResponseFromTransformer]
 * documented the transformer it replaces. Both render the first model in the database, so
 * the two examples are built from the same row.
 */

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->createServerModel();

    $config = config('scribe');
    $config['examples']['models_source'] = ['databaseFirst'];

    $this->strategy = new class(new DocumentationConfig($config)) extends UsePterodactylResponseAttributes
    {
        /** @return array{status: int, description: string|null, content: string|false} */
        public function render(ResponseFromTransformer|ResponseFromResource $attribute): array
        {
            return $attribute instanceof ResponseFromResource ? $this->getApiResourceResponse($attribute) : $this->getTransformerResponse($attribute);
        }
    };
});

test('resources are documented as their transformers were', function (ResponseFromTransformer $transformer, ResponseFromResource $resource, string $expected): void {
    $documented = $this->strategy->render($transformer);

    expect($this->strategy->render($resource))->toBe($documented)
        ->and($documented['content'])->toContain($expected);
})->with([
    'an item with an include, a status and meta' => [
        new ResponseFromTransformer(ServerVariableTransformer::class, EggVariable::class, status: 201, description: 'Variable created.', resourceKey: 'server_variable', meta: ['resource' => 'https://panel.example.test/api/application/servers/1'], include: ['parent']),
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, status: 201, description: 'Variable created.', resourceKey: 'server_variable', meta: ['resource' => 'https://panel.example.test/api/application/servers/1'], include: ['parent']),
        '"attributes":null}}},"meta":{"resource":"https:\/\/panel.example.test\/api\/application\/servers\/1"}}',
    ],
    'a paginated list with meta after the pagination' => [
        new ResponseFromTransformer(ServerVariableTransformer::class, EggVariable::class, collection: true, resourceKey: 'server_variable', paginate: [IlluminatePaginatorAdapter::class, 50], meta: ['note' => 'example']),
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, collection: true, resourceKey: 'server_variable', paginate: 50, meta: ['note' => 'example']),
        '"meta":{"pagination":{"total":1,"count":1,"per_page":50,"current_page":1,"total_pages":1,"links":[]},"note":"example"}',
    ],
    'an item without a resource key' => [
        new ResponseFromTransformer(ServerVariableTransformer::class, EggVariable::class, include: ['parent']),
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, include: ['parent']),
        '{"object":null,"attributes":',
    ],
    'a list with counts loaded after the model and a gated include' => [
        new ResponseFromTransformer(ServerVariableTransformer::class, EggVariable::class, collection: true, resourceKey: 'server_variable', include: ['parent'], withCount: ['serverVariable']),
        new ResponseFromResource(ServerVariableResource::class, EggVariable::class, collection: true, resourceKey: 'server_variable', include: ['parent'], withCount: ['serverVariable']),
        '"server_variable_count":0,"relationships":{"parent":{"object":"null_resource","attributes":null}}',
    ],
]);
