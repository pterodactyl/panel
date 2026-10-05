<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\PaginationValidationTest;

use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;

uses(\Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase::class);
dataset('listEndpointProvider', function () {
    return ['users' => ['/api/application/users?per_page=invalid'], 'nodes' => ['/api/application/nodes?per_page=invalid'], 'locations' => ['/api/application/locations?per_page=invalid'], 'eggs' => ['/api/application/eggs?per_page=invalid'], 'servers' => ['/api/application/servers?per_page=invalid'], 'allocations' => ['/api/application/nodes/{node}/allocations?per_page=invalid'], 'deployable nodes' => ['/api/application/nodes/deployable?memory=0&disk=0&page=1&per_page=invalid']];
});
test('invalid page sizes return validation errors for every list endpoint', function (string $endpoint) {
    if (str_contains($endpoint, '{node}')) {
        $node = Node::factory()->for(Location::factory())->create();
        $endpoint = str_replace('{node}', (string) $node->id, $endpoint);
    }
    $this->getJson($endpoint)->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'per_page');
})->with('listEndpointProvider');
test('page size is capped', function () {
    $this->getJson('/api/application/users?per_page=101')->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'per_page');
});
