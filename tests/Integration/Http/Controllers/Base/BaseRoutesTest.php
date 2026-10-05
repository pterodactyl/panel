<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\Controllers\Base\BaseRoutesTest;

use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

uses(HttpTestCase::class);
test('index renders the react shell for authenticated users', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertOk()->assertViewIs('templates.base.core');
    $this->actingAs($user)->get('/account')->assertOk()->assertViewIs('templates.base.core');
    $this->actingAs($user)->get('/server/some-uuid')->assertOk()->assertViewIs('templates.base.core');
});
test('guests also receive the react shell', function () {
    $this->get('/')->assertOk()->assertViewIs('templates.base.core');
});
test('locales are returned as json', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->getJson('/locales/locale.json?locale=en&namespace=auth');
    $response->assertOk();
    expect($response->json())->toBeArray();
    expect($response->json())->not->toBeEmpty();
});
