<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\TestResponse;

trait IntegrationJsonRequestAssertions
{
    /**
     * Make assertions about a 404 response on the API.
     *
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     */
    public function assertNotFoundJson(TestResponse $response)
    {
        $response->assertStatus(404);
        $response->assertJsonStructure(['errors' => [['code', 'status', 'detail']]]);
        $response->assertJsonCount(1, 'errors');
        $response->assertJson([
            'errors' => [
                [
                    'code' => 'NotFoundHttpException',
                    'status' => '404',
                    'detail' => 'The requested resource does not exist on this server.',
                ],
            ],
        ], true);
    }

    /**
     * Make assertions about a 403 error returned by the API.
     *
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     */
    public function assertAccessDeniedJson(TestResponse $response)
    {
        $response->assertStatus(403);
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
