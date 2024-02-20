<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Notifications\MailTested;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Providers\SettingsServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Settings\MailSettingsFormRequest;

class MailController extends Controller
{
    /**
     * MailController constructor.
     */
    public function __construct(
        private ConfigRepository $config,
        private Encrypter $encrypter,
        private Kernel $kernel,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {
    }

    /**
     * Render UI for editing mail settings. This UI should only display if
     * the server is configured to send mail using SMTP.
     */
    public function index(): View
    {
        return $this->view->make('admin.settings.mail', [
            'disabled' => $this->config->get('mail.default') !== 'smtp',
        ]);
    }

    /**
     * Handle request to update SMTP mail settings.
     *
     * @throws DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(MailSettingsFormRequest $request): Response
    {
        if ($this->config->get('mail.default') !== 'smtp') {
            throw new DisplayException('This feature is only available if SMTP is the selected email driver for the Panel.');
        }

        $values = $request->normalize();
        if (array_get($values, 'mail:mailers:smtp:password') === '!e') {
            $values['mail:mailers:smtp:password'] = '';
        }

        foreach ($values as $key => $value) {
            if (in_array($key, SettingsServiceProvider::getEncryptedKeys()) && !empty($value)) {
                $value = $this->encrypter->encrypt($value);
            }

            $this->settings->set('settings::' . $key, $value);
        }

        $this->kernel->call('queue:restart');

        return response('', 204);
    }

    /**
     * Submit a request to send a test mail message.
     */
    public function test(Request $request): Response
    {
        try {
            Notification::route('mail', $request->user()->email)
                ->notify(new MailTested($request->user()));
        } catch (\Exception $exception) {
            return response($exception->getMessage(), 500);
        }

        return response('', 204);
    }
}
