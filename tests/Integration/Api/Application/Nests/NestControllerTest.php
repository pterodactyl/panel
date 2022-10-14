<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Nests;

use Illuminate\Http\Response;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\NestTransformer;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class NestControllerTest extends ApplicationApiIntegrationTestCase
{
    private NestRepositoryInterface $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(NestRepositoryInterface::class);
    }

    /**
     * Test that the expected nests are returned by the request.
     */
    public function testNestResponse()
    {
        /** @var \Pterodactyl\Models\Nest[] $nests */
        $nests = $this->repository->all();

        $response = $this->getJson('/api/application/nests');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(count($nests), 'data');
        $response->assertJsonStructure([
            'object',
            'data' => [['object', 'attributes' => ['id', 'uuid', 'author', 'name', 'description', 'created_at', 'updated_at']]],
            'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']],
        ]);

        $response->assertJson([
            'object' => 'list',
            'data' => [],
            'meta' => [
                'pagination' => [
                    'total' => 4,
                    'count' => 4,
                    'per_page' => 50,
                    'current_page' => 1,
                    'total_pages' => 1,
                ],
            ],
        ]);

        foreach ($nests as $nest) {
            $response->assertJsonFragment([
                'object' => 'nest',
                'attributes' => $this->getTransformer(NestTransformer::class)->transform($nest),
            ]);
        }
    }

    /**
     * Test that getting a single nest returns the expected result.
     */
    public function testSingleNestResponse()
    {
        $nest = $this->repository->find(1);

        $response = $this->getJson('/api/application/nests/' . $nest->id);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => ['id', 'uuid', 'author', 'name', 'description', 'created_at', 'updated_at'],
        ]);

        $response->assertJson([
            'object' => 'nest',
            'attributes' => $this->getTransformer(NestTransformer::class)->transform($nest),
        ]);
    }

    /**
     * Test that including eggs in the response works as expected.
     */
    public function testSingleNestWithEggsIncluded()
    {
        $nest = $this->repository->find(1);
        $nest->loadMissing('eggs');

        $response = $this->getJson('/api/application/nests/' . $nest->id . '?include=servers,eggs');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'relationships' => [
                    'eggs' => ['object', 'data' => []],
                    'servers' => ['object', 'data' => []],
                ],
            ],
        ]);

        $response->assertJsonCount(count($nest->getRelation('eggs')), 'attributes.relationships.eggs.data');
    }

    /**
     * Test that a missing nest returns a 404 error.
     */
    public function testGetMissingNest()
    {
        $response = $this->getJson('/api/application/nests/nil');
        $this->assertNotFoundJson($response);
    }

    /**
     * Test that an authentication error occurs if a key does not have permission
     * to access a resource.
     */
    public function testErrorReturnedIfNoPermission()
    {
        $nest = $this->repository->find(1);
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_nests' => 0]);

        $response = $this->getJson('/api/application/nests/' . $nest->id);
        $this->assertAccessDeniedJson($response);
    }
}
