<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Illuminate\Contracts\Config\Repository;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class TwoFactorSetupServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \PragmaRX\Google2FA\Contracts\Google2FA
     */
    protected $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->google2FA = m::mock(Google2FA::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->service = new TwoFactorSetupService($this->config, $this->google2FA, $this->repository);
    }

    /**
     * Test that the correct data is returned.
     */
    public function testSecretAndImageAreReturned()
    {
        $model = factory(User::class)->make();

        $this->google2FA->shouldReceive('generateSecretKey')->withNoArgs()->once()->andReturn('secretKey');
        $this->config->shouldReceive('get')->with('app.name')->once()->andReturn('CompanyName');
        $this->google2FA->shouldReceive('getQRCodeGoogleUrl')->with('CompanyName', $model->email, 'secretKey')
            ->once()->andReturn('http://url.com');
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($model->id, ['totp_secret' => 'secretKey'])->once()->andReturnNull();

        $response = $this->service->handle($model);
        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('qrImage', $response);
        $this->assertArrayHasKey('secret', $response);
        $this->assertEquals('http://url.com', $response['qrImage']);
        $this->assertEquals('secretKey', $response['secret']);
    }

    /**
     * Test that an integer can be passed in place of the user model.
     */
    public function testIntegerCanBePassedInPlaceOfUserModel()
    {
        $model = factory(User::class)->make();

        $this->repository->shouldReceive('find')->with($model->id)->once()->andReturn($model);
        $this->google2FA->shouldReceive('generateSecretKey')->withNoArgs()->once()->andReturnNull();
        $this->config->shouldReceive('get')->with('app.name')->once()->andReturnNull();
        $this->google2FA->shouldReceive('getQRCodeGoogleUrl')->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh->update')->once()->andReturnNull();

        $this->assertTrue(is_array($this->service->handle($model->id)));
    }
}
