<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Enum\EggSpecificTagsTest;

use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('is special', function () {
    expect(EggSpecificTags::isSpecial('minecraft'))->toBeTrue();
    expect(EggSpecificTags::isSpecial('Minecraft'))->toBeFalse();
    expect(EggSpecificTags::isSpecial('my-custom-tag'))->toBeFalse();
});
test('try from slug folds case and whitespace', function () {
    expect(EggSpecificTags::tryFromSlug(' RUST '))->toBe(EggSpecificTags::Rust);
    expect(EggSpecificTags::tryFromSlug('GarrysMod'))->toBe(EggSpecificTags::GarrysMod);
    expect(EggSpecificTags::tryFromSlug(''))->toBeNull();
    expect(EggSpecificTags::tryFromSlug(null))->toBeNull();
    expect(EggSpecificTags::tryFromSlug('not-a-game'))->toBeNull();
});
test('try from slug matches the friendly name', function () {
    expect(EggSpecificTags::tryFromSlug('Source Engine'))->toBe(EggSpecificTags::Srcds);
    expect(EggSpecificTags::tryFromSlug('  source engine '))->toBe(EggSpecificTags::Srcds);
    expect(EggSpecificTags::tryFromSlug('Voice Servers'))->toBe(EggSpecificTags::Voice);
    expect(EggSpecificTags::tryFromSlug('Minecraft: Bedrock'))->toBe(EggSpecificTags::Bedrock);
    expect(EggSpecificTags::tryFromSlug("Garry's Mod"))->toBe(EggSpecificTags::GarrysMod);
    // The slug still wins over any name, so canonical values are unaffected.
    expect(EggSpecificTags::tryFromSlug('minecraft'))->toBe(EggSpecificTags::Minecraft);
});
test('case for prefers the specific override', function () {
    expect(EggSpecificTags::caseFor(['minecraft', 'bedrock']))->toBe(EggSpecificTags::Bedrock);
    expect(EggSpecificTags::caseFor(['minecraft']))->toBe(EggSpecificTags::Minecraft);
    expect(EggSpecificTags::caseFor(['my-custom-tag']))->toBeNull();
});
test('every case has a colour and name', function () {
    foreach (EggSpecificTags::cases() as $case) {
        expect($case->color())->toMatch('/^#[0-9A-Fa-f]{6}$/', $case->value);
        expect($case->friendlyName())->not->toBeEmpty($case->value);
    }
    expect(EggSpecificTags::all())->toHaveCount(count(EggSpecificTags::cases()));
});
