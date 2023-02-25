<?php

namespace Tests\Feature;

use App\Models\ApplicationApi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class TestApiAuthorization extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     *
     * @dataProvider ApiRoutesThatRequireAuthorization
     *
     * @return void
     * @test
     */
    public function test_api_route_without_auth_headers(string $method, string $route)
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->{$method}($route);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Missing Authorization header']);
    }

    /**
     * A basic feature test example.
     *
     * @dataProvider ApiRoutesThatRequireAuthorization
     *
     * @return void
     */
    public function test_api_route_with_auth_headers_but_invalid_token(string $method, string $route)
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.Str::random(48),
        ])->{$method}($route);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid Authorization token']);
    }

    /**
     * A basic feature test example.
     *
     * @dataProvider ApiRoutesThatRequireAuthorization
     *
     * @return void
     */
    public function test_api_route_with_valid_auth_headers(string $method, string $route)
    {
        $applicationApi = ApplicationApi::factory()->create();

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$applicationApi->token,
        ])->{$method}($route);

        $response->assertStatus(200);
    }

    public function ApiRoutesThatRequireAuthorization(): array
    {
        return [
            'List Users' => [
                'method' => 'get',
                'route' => '/api/users',
            ],
            'List Servers' => [
                'method' => 'get',
                'route' => '/api/servers',
            ],
        ];
    }
}
