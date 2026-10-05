<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Acl\Api\AdminAclTest;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
/**
 * Provide valid and invalid permissions combos for testing.
 */
dataset('permissionsDataProvider', function () {
    return [[AdminAcl::READ, AdminAcl::READ, true], [AdminAcl::READ | AdminAcl::WRITE, AdminAcl::READ, true], [AdminAcl::READ | AdminAcl::WRITE, AdminAcl::WRITE, true], [AdminAcl::WRITE, AdminAcl::WRITE, true], [AdminAcl::READ, AdminAcl::WRITE, false], [AdminAcl::NONE, AdminAcl::READ, false], [AdminAcl::NONE, AdminAcl::WRITE, false]];
});
test('permissions', function (int $permission, int $check, bool $outcome) {
    expect(AdminAcl::can($permission, $check))->toBe($outcome);
})->with('permissionsDataProvider');
test('check', function () {
    // user_id is supplied so the factory does not resolve its User relationship,
    // which Laravel expands by persisting the parent even under make().
    $model = ApiKey::factory()->make(['user_id' => 1, 'r_servers' => AdminAcl::READ | AdminAcl::WRITE]);
    expect(AdminAcl::check($model, AdminAcl::RESOURCE_SERVERS, AdminAcl::WRITE))->toBeTrue();
});
