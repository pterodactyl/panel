<?php

namespace Pterodactyl\Tests\Traits\Http;

use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

trait IntegrationJsonRequestAssertions
{
    /**
     * Make assertions about a 404 response on the API.
     */
    public function assertNotFoundJson(TestResponse $response): void
    {
        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertJsonStructure(['errors' => [['code', 'status', 'detail']]]);
        $response->assertJsonCount(1, 'errors');
        $response->assertJson([
            'errors' => [
                [
                    'code' => 'NotFoundHttpException',
                    'status' => '404',
                    'detail' => 'The requested resource could not be found on the server.',
                ],
            ],
        ], true);
    }

    /**
     * Make assertions about a 403 error returned by the API.
     */
    public function assertAccessDeniedJson(TestResponse $response): void
    {
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $response->assertJsonStructure(['errors' => [['code', 'status', 'detail']]]);
        $response->assertJsonCount(1, 'errors');
        $response->assertJson([
            'errors' => [
                [
                    'code' => 'AccessDeniedHttpException',
                    'status' => '403',
                    'detail' => 'This action is unauthorized.',
                ],
            ],
        ], true);
    }
}
