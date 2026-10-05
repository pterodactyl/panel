<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\PaginationValidationTest;

use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);

test('admin list endpoints reject malformed pagination with 422', function (string $endpoint): void {
    if (str_contains($endpoint, '{node}')) {
        $node = Node::factory()->for(Location::factory())->create();
        $endpoint = str_replace('{node}', (string) $node->id, $endpoint);
    }

    $this->getJson($endpoint.'?per_page=invalid')
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'per_page');
})->with([
    'users' => '/api/admin/users',
    'nodes' => '/api/admin/nodes',
    'locations' => '/api/admin/locations',
    'eggs' => '/api/admin/eggs',
    'servers' => '/api/admin/servers',
    'mounts' => '/api/admin/mounts',
    'tags' => '/api/admin/tags',
    'api keys' => '/api/admin/api-keys',
    'database hosts' => '/api/admin/database-hosts',
    'allocations' => '/api/admin/nodes/{node}/allocations',
    'activity' => '/api/admin/activity',
]);

test('admin pagination rejects invalid bounds and shapes with 422', function (array $query, string $field): void {
    $this->getJson(route('api.admin.users', $query))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', $field);
})->with([
    'zero size' => [['per_page' => 0], 'per_page'],
    'negative size' => [['per_page' => -1], 'per_page'],
    'oversized page' => [['per_page' => 101], 'per_page'],
    'array size' => [['per_page' => [10]], 'per_page'],
    'zero page' => [['page' => 0], 'page'],
    'negative page' => [['page' => -1], 'page'],
    'non-integer page' => [['page' => 'invalid'], 'page'],
]);

test('admin pagination accepts integer query strings and preserves its default', function (): void {
    $this->getJson(route('api.admin.users'))
        ->assertOk()
        ->assertJsonPath('meta.pagination.per_page', 50);

    $this->getJson(route('api.admin.users', ['per_page' => '100', 'page' => '1']))
        ->assertOk()
        ->assertJsonPath('meta.pagination.per_page', 100);
});
