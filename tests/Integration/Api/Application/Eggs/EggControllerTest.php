<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Transformers\Api\Application\EggTransformer;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class EggControllerTest extends ApplicationApiIntegrationTestCase
{
    private EggRepositoryInterface $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(EggRepositoryInterface::class);
    }

    /**
     * Test that all the eggs belonging to a given nest can be returned.
     */
    public function testListAllEggsInNest()
    {
        $eggs = $this->repository->findWhere([['nest_id', '=', 1]]);

        $response = $this->getJson('/api/application/nests/' . $eggs->first()->nest_id . '/eggs');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(count($eggs), 'data');
        $response->assertJsonStructure([
            'object',
            'data' => [
                [
                    'object',
                    'attributes' => [
                        'id', 'uuid', 'nest_id', 'author', 'description', 'docker_images', 'startup', 'created_at', 'updated_at',
                        'script' => ['privileged', 'install', 'entry', 'container', 'extends'],
                        'config' => [
                            'files' => [],
                            'startup' => ['done'],
                            'stop',
                            'extends',
                        ],
                    ],
                ],
            ],
        ]);

        foreach (array_get($response->json(), 'data') as $datum) {
            $egg = $eggs->where('id', '=', $datum['attributes']['id'])->first();

            $expected = json_encode(Arr::sortRecursive($datum['attributes']));
            $actual = json_encode(Arr::sortRecursive((new EggTransformer())->transform($egg)));

            $this->assertJsonStringEqualsJsonString(
                $expected,
                $actual,
                'Unable to find JSON fragment: ' . PHP_EOL . PHP_EOL . "[{$expected}]" . PHP_EOL . PHP_EOL . 'within' . PHP_EOL . PHP_EOL . "[{$actual}]."
            );
        }
    }

    /**
     * Test that a single egg can be returned.
     */
    public function testReturnSingleEgg()
    {
        $egg = $this->repository->find(1);

        $response = $this->getJson('/api/application/eggs/' . $egg->id);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'id', 'uuid', 'nest_id', 'author', 'description', 'docker_images', 'startup', 'script' => [], 'config' => [], 'created_at', 'updated_at',
            ],
        ]);

        $response->assertJson([
            'object' => 'egg',
            'attributes' => json_decode(json_encode((new EggTransformer())->transform($egg)), true),
        ], true);
    }

    /**
     * Test that a single egg and all the defined relationships can be returned.
     */
    public function testReturnSingleEggWithRelationships()
    {
        $egg = $this->repository->find(1);

        $response = $this->getJson('/api/application/eggs/' . $egg->id . '?include=servers,variables,nest');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'relationships' => [
                    'nest' => ['object', 'attributes'],
                    'servers' => ['object', 'data' => []],
                    'variables' => ['object', 'data' => []],
                ],
            ],
        ]);
    }

    /**
     * Test that a missing egg returns a 404 error.
     */
    public function testGetMissingEgg()
    {
        $response = $this->getJson('/api/application/eggs/0');
        $this->assertNotFoundJson($response);
    }

    /**
     * Test that an authentication error occurs if a key does not have permission
     * to access a resource.
     */
    public function testErrorReturnedIfNoPermission()
    {
        $this->markTestSkipped('todo: implement proper admin api key permissions system');
    }
}
