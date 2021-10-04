<?php

namespace Pterodactyl\Tests\Unit\Services\Api;

use Mockery as m;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Tests\TestCase;
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
    public function setUp(): void
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
        $model = ApiKey::factory()->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturnCallback(function ($length) {
                return 'str_' . $length;
            });

        $this->encrypter->shouldReceive('encrypt')->with('str_' . ApiKey::KEY_LENGTH)->once()->andReturn($model->token);

        $this->repository->shouldReceive('create')->with([
            'test-data' => 'test',
            'key_type' => ApiKey::TYPE_NONE,
            'identifier' => 'str_' . ApiKey::IDENTIFIER_LENGTH,
            'token' => $model->token,
        ], true, true)->once()->andReturn($model);

        $response = $this->getService()->handle(['test-data' => 'test']);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ApiKey::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Test that an identifier is only set by the function.
     */
    public function testIdentifierAndTokenAreOnlySetByFunction()
    {
        $model = ApiKey::factory()->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturnCallback(function ($length) {
                return 'str_' . $length;
            });

        $this->encrypter->shouldReceive('encrypt')->with('str_' . ApiKey::KEY_LENGTH)->once()->andReturn($model->token);

        $this->repository->shouldReceive('create')->with([
            'key_type' => ApiKey::TYPE_NONE,
            'identifier' => 'str_' . ApiKey::IDENTIFIER_LENGTH,
            'token' => $model->token,
        ], true, true)->once()->andReturn($model);

        $response = $this->getService()->handle(['identifier' => 'customIdentifier', 'token' => 'customToken']);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ApiKey::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Test that permissions passed in are loaded onto the key data.
     */
    public function testPermissionsAreRetrievedForApplicationKeys()
    {
        $model = ApiKey::factory()->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturnCallback(function ($length) {
                return 'str_' . $length;
            });

        $this->encrypter->shouldReceive('encrypt')->with('str_' . ApiKey::KEY_LENGTH)->once()->andReturn($model->token);

        $this->repository->shouldReceive('create')->with([
            'key_type' => ApiKey::TYPE_APPLICATION,
            'identifier' => 'str_' . ApiKey::IDENTIFIER_LENGTH,
            'token' => $model->token,
            'permission-key' => 'exists',
        ], true, true)->once()->andReturn($model);

        $response = $this->getService()->setKeyType(ApiKey::TYPE_APPLICATION)->handle([], ['permission-key' => 'exists']);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ApiKey::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Test that permissions are not retrieved for any key that is not an application key.
     *
     * @dataProvider keyTypeDataProvider
     */
    public function testPermissionsAreNotRetrievedForNonApplicationKeys($keyType)
    {
        $model = ApiKey::factory()->make();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Api', 'str_random')
            ->expects($this->exactly(2))->willReturnCallback(function ($length) {
                return 'str_' . $length;
            });

        $this->encrypter->shouldReceive('encrypt')->with('str_' . ApiKey::KEY_LENGTH)->once()->andReturn($model->token);

        $this->repository->shouldReceive('create')->with([
            'key_type' => $keyType,
            'identifier' => 'str_' . ApiKey::IDENTIFIER_LENGTH,
            'token' => $model->token,
        ], true, true)->once()->andReturn($model);

        $response = $this->getService()->setKeyType($keyType)->handle([], ['fake-permission' => 'should-not-exist']);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ApiKey::class, $response);
        $this->assertSame($model, $response);
    }

    /**
     * Provide key types that are not an application specific key.
     */
    public function keyTypeDataProvider(): array
    {
        return [
            [ApiKey::TYPE_NONE], [ApiKey::TYPE_ACCOUNT], [ApiKey::TYPE_DAEMON_USER], [ApiKey::TYPE_DAEMON_APPLICATION],
        ];
    }

    /**
     * Return an instance of the service with mocked dependencies for testing.
     */
    private function getService(): KeyCreationService
    {
        return new KeyCreationService($this->repository, $this->encrypter);
    }
}
