<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Mounts\AttachToMountTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Mounts\AttachesEggsToMounts;
use Pterodactyl\Contracts\Mounts\AttachesNodesToMounts;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** Attaches the ids to the mount as eggs or as nodes. */
function attach(Mount $mount, string $kind, array $ids): void
{
    $kind === 'eggs'
        ? app(AttachesEggsToMounts::class)->attach($mount, $ids)
        : app(AttachesNodesToMounts::class)->attach($mount, $ids);
}

/** An egg or a node to attach. */
function attachable(string $kind): Egg|Node
{
    return $kind === 'eggs' ? Egg::factory()->create() : Node::factory()->withLocation()->create();
}

test('ids attached without the http api meet the same rules', function (string $kind, array $ids, string $field): void {
    $mount = Mount::factory()->create();
    $existing = attachable($kind);

    try {
        attach($mount, $kind, array_map(fn (bool|int|string|null $id): bool|int|string|null => $id === 'existing' ? $existing->id : $id, $ids));
        $this->fail('The ids were attached.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$kind === 'eggs' ? $field : str_replace('eggs', 'nodes', $field)]);
    }

    expect($mount->{$kind}()->exists())->toBeFalse();
})->with(['eggs', 'nodes'])->with([
    'no ids' => [[], 'eggs'],
    'an id that names nothing' => [['existing', 999999], 'eggs.1'],
    'an id that is a boolean' => [[true], 'eggs.0'],
    'an id that is null' => [[null], 'eggs.0'],
    'an id that is not a number' => [['existing', 'abc'], 'eggs.1'],
    'ids that are not a list' => [['selected' => 'existing'], 'eggs'],
]);

test('ids may be numbers or numeric strings, and attached ids are kept', function (string $kind): void {
    $mount = Mount::factory()->create();
    $first = attachable($kind);
    $second = attachable($kind);

    attach($mount, $kind, [$first->id]);
    attach($mount, $kind, [(string) $second->id, $second->id]);

    expect($mount->{$kind}()->pluck('id')->sort()->values()->all())->toBe([$first->id, $second->id]);
})->with(['eggs', 'nodes']);
