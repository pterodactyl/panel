<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Allocations\CreateAllocationsTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Allocations\CreatesAllocations;
use Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException;
use Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('allocations created without the http api meet the same rules', function (array $input, string $field): void {
    $node = Node::factory()->withLocation()->create();

    try {
        $this->app->make(CreatesAllocations::class)->create($node, [...['allocation_ip' => '10.0.0.1', 'allocation_ports' => ['25565']], ...$input]);
        $this->fail('The allocations were created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($node->allocations()->count())->toBe(0);
})->with([
    'a missing address' => [['allocation_ip' => null], 'allocation_ip'],
    'an address that is not a string' => [['allocation_ip' => ['10.0.0.1']], 'allocation_ip'],
    'no ports' => [['allocation_ports' => []], 'allocation_ports'],
    'ports that are not an array' => [['allocation_ports' => '25565'], 'allocation_ports'],
    'a port that is a boolean' => [['allocation_ports' => [true]], 'allocation_ports.0'],
    'a port that is a list' => [['allocation_ports' => [['25565']]], 'allocation_ports.0'],
    'an alias that is too long' => [['allocation_alias' => str_repeat('a', 256)], 'allocation_alias'],
]);

test('ports may be numbers or numeric strings, and ranges are opened port by port', function (): void {
    $node = Node::factory()->withLocation()->create();

    $this->app->make(CreatesAllocations::class)->create($node, ['allocation_ip' => '10.0.0.2', 'allocation_ports' => [25565, '25600', '25570-25572'], 'allocation_alias' => 'play.example.com']);

    $allocations = $node->allocations()->orderBy('port')->get();
    expect($allocations->pluck('port')->all())->toBe([25565, 25570, 25571, 25572, 25600])
        ->and($allocations->pluck('ip_alias')->unique()->all())->toBe(['play.example.com']);
});

test('a blank alias is stored as no alias', function (): void {
    $node = Node::factory()->withLocation()->create();

    $this->app->make(CreatesAllocations::class)->create($node, ['allocation_ip' => '10.0.0.3', 'allocation_ports' => ['25565'], 'allocation_alias' => '']);

    expect($node->allocations()->sole()->ip_alias)->toBeNull();
});

test('a port mapping that passes the rules but cannot be opened is still refused', function (): void {
    $node = Node::factory()->withLocation()->create();
    $allocations = $this->app->make(CreatesAllocations::class);

    expect(fn () => $allocations->create($node, ['allocation_ip' => '10.0.0.4', 'allocation_ports' => ['game']]))->toThrow(InvalidPortMappingException::class)
        ->and(fn () => $allocations->create($node, ['allocation_ip' => '10.0.0.4', 'allocation_ports' => [80]]))->toThrow(PortOutOfRangeException::class)
        ->and($node->allocations()->count())->toBe(0);
});
