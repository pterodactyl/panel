<?php

namespace Tests\Feature;

use App\Models\UsefulLink;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class TestUsefulLinksController
 */
class TestUsefulLinksController extends TestCase
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
        UsefulLink::factory()->create([
            'id' => 1,
        ]);

        $response = $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'pterodactyl_id' => '1',
        ]))->{$method}($route);

        $response->assertStatus($expectedStatus);
    }

    /**
     * @dataProvider usefulLinkDataProvider
     *
     * @param  array  $dataSet
     * @param  int  $expectedCount
     * @param  bool  $assertValidationErrors
     */
    public function test_creating_useful_link(array $dataSet, int $expectedCount, bool $assertValidationErrors)
    {
        $response = $this->actingAs($this->getTestUser())->post(route('admin.usefullinks.store'), $dataSet);

        if ($assertValidationErrors) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertSessionHasNoErrors();
        }

        $response->assertRedirect();
        $this->assertDatabaseCount('useful_links', $expectedCount);
    }

    /**
     * @dataProvider usefulLinkDataProvider
     *
     * @param  array  $dataSet
     * @param  int  $expectedCount
     * @param  bool  $assertValidationErrors
     */
    public function test_updating_useful_link(array $dataSet, int $expectedCount, bool $assertValidationErrors)
    {
        $link = UsefulLink::factory()->create([
            'id' => 1,
        ]);

        $response = $this->actingAs($this->getTestUser())->patch(route('admin.usefullinks.update', $link->id), $dataSet);

        if ($assertValidationErrors) {
            $response->assertSessionHasErrors();
        } else {
            $response->assertSessionHasNoErrors();
        }

        $response->assertRedirect();
        $this->assertDatabaseCount('useful_links', 1);
    }

    public function test_deleting_useful_link()
    {
        $link = UsefulLink::factory()->create([
            'id' => 1,
        ]);

        $response = $this->actingAs($this->getTestUser())->delete(route('admin.usefullinks.update', $link->id));

        $response->assertRedirect();
        $this->assertDatabaseCount('useful_links', 0);
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
     * @return array
     */
    public function usefulLinkDataProvider(): array
    {
        return [
            'Valid dataset 1' => [
                'dataSet' => [
                    'icon' => 'fas fa-user',
                    'title' => 'Bitsec.Dev Dashboard',
                    'link' => 'https://manage.bitsec.dev.com',
                    'description' => Str::random(1500),
                ],
                'expectedCount' => 1,
                'assertValidationErrors' => false,
            ],
            'Valid dataset 2' => [
                'dataSet' => [
                    'icon' => 'fas fa-user',
                    'title' => Str::random(30),
                    'link' => 'https://somerandomsite.com',
                    'description' => Str::random(1500),
                ],
                'expectedCount' => 1,
                'assertValidationErrors' => false,
            ],
            'Invalid dataset (invalid link)' => [
                'dataSet' => [
                    'icon' => 'fas fa-user',
                    'title' => 'Some Random Title',
                    'link' => '1221',
                    'description' => '<p>Some Random HTML</p>',
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (no title)' => [
                'dataSet' => [
                    'icon' => 'fas fa-user',
                    'title' => '',
                    'link' => 'https://somerandomsite.com',
                    'description' => '<p>Some Random HTML</p>',
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (to long title)' => [
                'dataSet' => [
                    'icon' => 'fas fa-user',
                    'title' => Str::random(200),
                    'link' => 'https://valid.com',
                    'description' => '<p>Some Random HTML</p>',
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (to long description)' => [
                'dataSet' => [
                    'icon' => 'fas fa-user',
                    'title' => 'Some Random Valid Title',
                    'link' => 'https://valid.com',
                    'description' => Str::random(2100),
                ],
                'expectedCount' => 0,
                'assertValidationErrors' => true,
            ],
            'Invalid dataset (no icon)' => [
                'dataSet' => [
                    'title' => 'Some Random Valid Title',
                    'link' => 'https://valid.com',
                    'description' => Str::random(200),
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
                'route' => '/admin/usefullinks',
                'expectedStatus' => 200,
            ],
            'Create page' => [
                'method' => 'get',
                'route' => '/admin/usefullinks/create',
                'expectedStatus' => 200,
            ],
            'Edit page' => [
                'method' => 'get',
                'route' => '/admin/usefullinks/1/edit',
                'expectedStatus' => 200,
            ],
        ];
    }
}
