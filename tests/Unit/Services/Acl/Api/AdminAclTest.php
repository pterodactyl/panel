<?php

namespace Pterodactyl\Tests\Unit\Services\Acl\Api;

use Pterodactyl\Models\ApiKey;
use Pterodactyl\Tests\TestCase;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class AdminAclTest extends TestCase
{
    /**
     * Test that permissions return the expects values.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsDataProvider')]
    public function testPermissions(int $permission, int $check, bool $outcome)
    {
        $this->assertSame($outcome, AdminAcl::can($permission, $check));
    }

    /**
     * Test that checking against a model works as expected.
     */
    public function testCheck()
    {
        $model = ApiKey::factory()->make(['r_servers' => AdminAcl::READ | AdminAcl::WRITE]);

        $this->assertTrue(AdminAcl::check($model, AdminAcl::RESOURCE_SERVERS, AdminAcl::WRITE));
    }

    /**
     * Provide valid and invalid permissions combos for testing.
     */
    public static function permissionsDataProvider(): array
    {
        return [
            [AdminAcl::READ, AdminAcl::READ, true],
            [AdminAcl::READ | AdminAcl::WRITE, AdminAcl::READ, true],
            [AdminAcl::READ | AdminAcl::WRITE, AdminAcl::WRITE, true],
            [AdminAcl::WRITE, AdminAcl::WRITE, true],
            [AdminAcl::READ, AdminAcl::WRITE, false],
            [AdminAcl::NONE, AdminAcl::READ, false],
            [AdminAcl::NONE, AdminAcl::WRITE, false],
        ];
    }
}
