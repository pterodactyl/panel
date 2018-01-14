<?php

namespace Tests\Unit\Services\Api;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\ApiKey;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class KeyCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter|\Mockery\Mock
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(ApiKeyRepositoryInterface::class);
    }

    /**
     * Test that the service is able to create a keypair and assign the correct permissions.
     */
    public function testKeyIsCreated()
    {
        $model = factory(ApiKey::class)->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturnCallback(function ($length) {
                return 'str_' . $length;
            });

        $this->encrypter->shouldReceive('encrypt')->with('str_' . ApiKey::KEY_LENGTH)->once()->andReturn($model->token);

        $this->repository->shouldReceive('create')->with([
            'test-data' => 'test',
            'identifier' => 'str_' . ApiKey::IDENTIFIER_LENGTH,
            'token' => $model->token,
        ], true, true)->once()->andReturn($model);

        $response = $this->getService()->handle(['test-data' => 'test']);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ApiKey::class, $response);
        $this->assertSame($model, $response);
    }

    public function testIdentifierAndTokenAreOnlySetByFunction()
    {
        $model = factory(ApiKey::class)->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturnCallback(function ($length) {
                return 'str_' . $length;
            });

        $this->encrypter->shouldReceive('encrypt')->with('str_' . ApiKey::KEY_LENGTH)->once()->andReturn($model->token);

        $this->repository->shouldReceive('create')->with([
            'identifier' => 'str_' . ApiKey::IDENTIFIER_LENGTH,
            'token' => $model->token,
        ], true, true)->once()->andReturn($model);

        $response = $this->getService()->handle(['identifier' => 'customIdentifier', 'token' => 'customToken']);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ApiKey::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Return an instance of the service with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Services\Api\KeyCreationService
     */
    private function getService(): KeyCreationService
    {
        return new KeyCreationService($this->repository, $this->encrypter);
    }
}
