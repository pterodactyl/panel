<?php

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class TwoFactorSetupServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter|\Mockery\Mock
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(UserRepositoryInterface::class);
    }

    /**
     * Test that the correct data is returned.
     */
    public function testSecretAndImageAreReturned()
    {
        $model = factory(User::class)->make();

        $this->config->shouldReceive('get')->with('pterodactyl.auth.2fa.bytes', 16)->andReturn(32);
        $this->config->shouldReceive('get')->with('app.name')->andReturn('Company Name');
        $this->encrypter->shouldReceive('encrypt')
            ->with(m::on(function ($value) {
                return preg_match('/([A-Z234567]{32})/', $value) !== false;
            }))
            ->once()
            ->andReturn('encryptedSecret');

        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, ['totp_secret' => 'encryptedSecret'])->once()->andReturnNull();

        $response = $this->getService()->handle($model);
        $this->assertNotEmpty($response);

        $companyName = preg_quote(rawurlencode('CompanyName'));
        $email = preg_quote(rawurlencode($model->email));

        $this->assertRegExp(
            '/otpauth:\/\/totp\/' . $companyName . ':' . $email . '\?secret=([A-Z234567]{32})&issuer=' . $companyName . '/',
            $response
        );
    }

    /**
     * Return an instance of the service to test with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    private function getService(): TwoFactorSetupService
    {
        return new TwoFactorSetupService($this->config, $this->encrypter, $this->repository);
    }
}
