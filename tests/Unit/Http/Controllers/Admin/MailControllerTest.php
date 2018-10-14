<?php

namespace Tests\Unit\Http\Controllers;

use Mockery as m;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Http\Controllers\Admin\Settings\MailController;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class MailControllerTest extends ControllerTestCase
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $configRepository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    private $kernel;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settingsRepositoryInterface;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->configRepository = m::mock(ConfigRepository::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->kernel = m::mock(Kernel::class);
        $this->settingsRepositoryInterface = m::mock(SettingsRepositoryInterface::class);
    }

    /**
     * Test the mail controller for viewing mail settings page.
     */
    public function testIndex()
    {
        $this->configRepository->shouldReceive('get');

        $response = $this->getController()->index();

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('admin.settings.mail', $response);
    }

    /**
     * Prepare a MailController using our mocks.
     *
     * @return MailController
     */
    public function getController()
    {
        return new MailController(
            $this->alert,
            $this->configRepository,
            $this->encrypter,
            $this->kernel,
            $this->settingsRepositoryInterface
        );
    }
}
