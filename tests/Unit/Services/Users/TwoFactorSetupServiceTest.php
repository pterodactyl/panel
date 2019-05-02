<?php

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use PragmaRX\Google2FAQRCode\Google2FA;
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
     * @var PragmaRX\Google2FAQRCode\Google2FA|\Mockery\Mock
     */
    private $google2FA;

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
        $this->google2FA = m::mock(Google2FA::class);
        $this->repository = m::mock(UserRepositoryInterface::class);
    }

    /**
     * Test that the correct data is returned.
     */
    public function testSecretAndImageAreReturned()
    {
        $model = factory(User::class)->make();

        config()->set('pterodactyl.auth.2fa.bytes', 32);
        config()->set('app.name', 'CompanyName');

        $this->google2FA->shouldReceive('generateSecretKey')->with(32)->once()->andReturn('secretKey');
        $this->config->shouldReceive('get')->with('app.name')->once()->andReturn('CompanyName');
        $this->google2FA->shouldReceive('getQRCodeInline')->with('CompanyName', $model->email, 'secretKey')->once()->andReturn('http://url.com');
        $this->encrypter->shouldReceive('encrypt')->with('secretKey')->once()->andReturn('encryptedSecret');
        $this->repository->shouldReceive('withoutFreshModel->update')->with($model->id, ['totp_secret' => 'encryptedSecret'])->once()->andReturnNull();

        $response = $this->getService()->handle($model);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertSame('http://url.com', $response->get('image'));
        $this->assertSame('secretKey', $response->get('secret'));
    }

    /**
     * Return an instance of the service to test with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    private function getService(): TwoFactorSetupService
    {
        return new TwoFactorSetupService($this->encrypter, $this->google2FA, $this->repository);
    }
}
