<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class TestUsefulLinksController
 */
class TestVouchersController extends TestCase
{
    use DatabaseTransactions;

    /**
     * @dataProvider accessibleRoutesDataProvider
     *
     * @param  string  $method
     * @param  string  $route
     * @param  int  $expectedStatus
     */
    public function test_accessible_routes(string $method, string $route, int $expectedStatus)
    {
        Voucher::factory()->create([
            'id' => 1,
        ]);

        $response = $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'pterodactyl_id' => '1',
        ]))->{$method}($route);

        $response->assertStatus($expectedStatus);
    }

    /**
     * @dataProvider VoucherDataProvider
     *
     * @param  array  $dataSet
     * @param  int  $expectedCount
     * @param  bool  $assertValidationErrors
     */
    public function test_creating_vouchers(array $dataSet, int $expectedCount, bool $assertValidationErrors)
    {
        $response = $this->actingAs($this->getTestUser())->post(route('admin.vouchers.store'), $dataSet);

        if ($assertValidationErrors) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertSessionHasNoErrors();
        }

        $response->assertRedirect();
        $this->assertDatabaseCount('vouchers', $expectedCount);
    }

    /**
     * @return User
     */
    private function getTestUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'pterodactyl_id' => '1',
        ]);
    }

    /**
     * @dataProvider VoucherDataProvider
     *
     * @param  array  $dataSet
     * @param  int  $expectedCount
     * @param  bool  $assertValidationErrors
     */
    public function test_updating_voucher(array $dataSet, int $expectedCount, bool $assertValidationErrors)
    {
        $voucher = Voucher::factory()->create([
            'id' => 1,
        ]);

        $response = $this->actingAs($this->getTestUser())->patch(route('admin.vouchers.update', $voucher->id), $dataSet);

        if ($assertValidationErrors) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertSessionHasNoErrors();
        }

        $response->assertRedirect();
        $this->assertDatabaseCount('vouchers', 1);
    }

    public function test_deleting_vouchers()
    {
        $voucher = Voucher::factory()->create([
            'id' => 1,
        ]);

        $response = $this->actingAs($this->getTestUser())->delete(route('admin.vouchers.update', $voucher->id));

        $response->assertRedirect();
        $this->assertDatabaseCount('vouchers', 0);
    }

    /**
     * @return array
     */
    public function VoucherDataProvider(): array
    {
        return [
            'Valid dataset 1' => [
                'dataSet' => [
                    'memo' => 'TESTING',
                    'code' => Str::random(20),
                    'credits' => 500,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 1,
                'assertValidationErrors' => false,
            ],
            'Valid dataset 2' => [
                'dataSet' => [
                    'code' => Str::random(36),
                    'credits' => 500,
                    'uses' => 500,
                ],
                'expectedCount' => 1,
                'assertValidationErrors' => false,
            ],
            'Valid dataset 3' => [
                'dataSet' => [
                    'memo' => 'TESTING',
                    'code' => Str::random(4),
                    'credits' => 1000000,
                    'uses' => 1,
                    'expires_at' => now()->addYears(6)->format('d-m-Y'),
                ],
                'expectedCount' => 1,
                'assertValidationErrors' => false,
            ],
            'Invalid dataset (memo to long)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(20),
                    'credits' => 500,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (code to short)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 500,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (code missing)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'credits' => 500,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (code to long)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(60),
                    'credits' => 500,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (credits missing)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (0 credits)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 0,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (to many credits)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 500,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (uses missing)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (0 uses)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 0,
                    'expires_at' => now()->addDay()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (expires_at today)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 500,
                    'expires_at' => now()->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (expires_at earlier)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 500,
                    'expires_at' => now()->subDays(5)->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (expires_at to far)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 500,
                    'expires_at' => now()->addYears(100)->format('d-m-Y'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (expires_at invalid format 1)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 500,
                    'expires_at' => now()->addYears(100)->format('Y-m-d'),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (expires_at invalid value)' => [
                'dataSet' => [
                    'memo' => Str::random(250),
                    'code' => Str::random(1),
                    'credits' => 99999999999,
                    'uses' => 500,
                    'expires_at' => Str::random(20),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function accessibleRoutesDataProvider(): array
    {
        return [
            'index page' => [
                'method' => 'get',
                'route' => '/admin/vouchers',
                'expectedStatus' => 200,
            ],
            'Create page' => [
                'method' => 'get',
                'route' => '/admin/vouchers/create',
                'expectedStatus' => 200,
            ],
            'Edit page' => [
                'method' => 'get',
                'route' => '/admin/vouchers/1/edit',
                'expectedStatus' => 200,
            ],
        ];
    }
}
