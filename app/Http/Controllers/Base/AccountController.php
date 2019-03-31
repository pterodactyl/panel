<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Support\Arr;
use Laravel\Socialite\Contracts\Factory;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class AccountController extends Controller
{
    use AvailableLanguages, OAuth2Providers;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Auth\SessionGuard
     */
    protected $sessionGuard;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * AccountController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param ConfigRepository $config
     * @param \Illuminate\Auth\AuthManager $authManager
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(AlertsMessageBag $alert,
                                ConfigRepository $config,
                                AuthManager $authManager,
                                UserUpdateService $updateService)
    {
        $this->alert = $alert;
        $this->config = $config;
        $this->updateService = $updateService;
        $this->sessionGuard = $authManager->guard();
    }

    /**
     * Display base account information page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if ($this->config->get('oauth2.enabled')) {
            $oauth2_ids = [];
            foreach (preg_split('~,~', \Auth::user()->oauth2_id) as $id) {
                // provider,value
                $split = preg_split('~:~', $id);
                if (! empty($split[1])) {
                    $oauth2_ids = Arr::add($oauth2_ids, $split[0], $split[1]);
                }
            }
        }

        return view('base.account', [
            'languages' => $this->getAvailableLanguages(true),
            'enabled_providers' => $this->config->get('oauth2.enabled') ? $this->getEnabledProviderSettings() : '',
            'oauth2_ids' => $this->config->get('oauth2.enabled') ? $oauth2_ids : '',
        ]);
    }

    /**
     * Update details for a user's account.
     *
     * @param \Pterodactyl\Http\Requests\Base\AccountDataFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(AccountDataFormRequest $request)
    {
        // Prevent logging this specific session out when the password is changed. This will
        // automatically update the user's password anyways, so no need to do anything else here.
        if ($request->input('do_action') === 'password') {
            $this->sessionGuard->logoutOtherDevices($request->input('new_password'));
        } elseif ($request->input('do_action') === 'oauth2_link') {
            // This code is not below since it redirects the user and doesnt update directly

            $driver = $request->get('oauth2_driver');

            // Check if the driver exists and is enabled else use the default one
            $driver = is_null($driver) ? $this->config->get('oauth2.default_driver') : $driver;
            $driver = Arr::has($this->getEnabledProviderSettings(), $driver) ? $driver : $this->config->get('oauth2.default_driver');

            // Save the driver the user's using
            session()->put('link_oauth2_driver', $driver);
            session()->save();

            return app(Factory::class)->driver($driver)
                ->scopes(preg_split('~,~', $this->config->get('oauth2.providers.' . $driver . '.scopes')))
                ->redirect();
        } else {
            if ($request->input('do_action') === 'email') {
                $data = ['email' => $request->input('new_email')];
            } elseif ($request->input('do_action') === 'identity') {
                $data = $request->only(['name_first', 'name_last', 'username', 'language', 'oauth2_id']);
            } elseif ($request->input('do_action') === 'oauth2_unlink') {
                $driver = $request->get('oauth2_driver');

                // Check if the driver exists and is enabled else use the default one
                $driver = is_null($driver) ? $this->config->get('oauth2.default_driver') : $driver;
                $driver = Arr::has($this->getEnabledProviderSettings(), $driver) ? $driver : $this->config->get('oauth2.default_driver');

                $oauth2_id = $request->user()->oauth2_id;

                // Resolves as [,]<provider>:<ID>
                preg_replace(',?' . $driver . ':.[^,]+', '', $oauth2_id);

                $data = compact('oauth2_id');
            } else {
                $data = [];
            }

            $this->updateService->setUserLevel(User::USER_LEVEL_USER);
            $this->updateService->handle($request->user(), $data);
        }
        $this->alert->success(trans('base.account.details_updated'))->flash();

        return redirect()->route('account');
    }
}
