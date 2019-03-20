<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;
use Pterodactyl\Traits\Helpers\OAuth2Providers;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use AvailableLanguages, OAuth2Providers;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

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
     * @param \Prologue\Alerts\AlertsMessageBag             $alert
     * @param \Illuminate\Auth\AuthManager                  $authManager
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(AlertsMessageBag $alert, AuthManager $authManager, UserUpdateService $updateService)
    {
        $this->alert = $alert;
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
        if (config('oauth2.enabled') == true) {
            $oauth2_ids = [];
            foreach (preg_split('~,~', \Auth::user()->getAttributes()['oauth2_id']) as $id) {
                $split = preg_split('~:~', $id);
                if (!empty($split[1])) $oauth2_ids = Arr::add($oauth2_ids, $split[0], $split[1]);
            }
        }

        return view('base.account', [
            'languages' => $this->getAvailableLanguages(true),
            'enabled_providers' => config('oauth2.enabled') == true ? $this->getEnabledProviderSettings() : '',
            'oauth2_ids' => config('oauth2.enabled') == true ? $oauth2_ids : '',
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
        } else {
            if ($request->input('do_action') === 'email') {
                $data = ['email' => $request->input('new_email')];
            } elseif ($request->input('do_action') === 'identity') {
                $data = $request->only(['name_first', 'name_last', 'username', 'language', 'oauth2_id']);
            } else {
                $data = [];
            }

            $this->updateService->setUserLevel(User::USER_LEVEL_USER);
            $this->updateService->handle($request->user(), $data);
        }

        $this->alert->success(trans('base.account.details_updated'))->flash();

        return redirect()->route('account');
    }

    /**
     * Link a user's account to an OAuth2 id
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function patch(Request $request)
    {
        $driver = $request->get('oauth2_driver');

        // Check if the driver exists and is enabled else use the default one
        $driver = is_null($driver) ? config('oauth2.default_driver') : $driver;
        $driver = Arr::has($this->getEnabledProviderSettings(), $driver) ? $driver : config('oauth2.default_driver');

        // Save the driver the user's using
        session()->put('link_oauth2_driver', $driver);
        session()->save();

        return Socialite::driver($driver)
            ->scopes(preg_split('~,~', config('oauth2.providers.' . $driver . '.scopes')))
            ->redirect();
    }

    /**
     * Unlink a user's account from an OAuth2 id
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(Request $request)
    {
        $driver = $request->get('oauth2_driver');

        // Check if the driver exists and is enabled else use the default one
        $driver = is_null($driver) ? config('oauth2.default_driver') : $driver;
        $driver = Arr::has($this->getEnabledProviderSettings(), $driver) ? $driver : config('oauth2.default_driver');

        $new_ids = [];
        // Remove the id
        foreach (preg_split('~,~', $request->user()->getAttributes()['oauth2_id']) as $id) {
            if (!Str::startsWith($id, $driver)) $new_ids = array_merge($new_ids, [$id]);
        }

        $oauth2_id = implode(',', $new_ids);

        $this->updateService->handle($request->user(), compact('oauth2_id'));

        $this->alert->success(trans('base.account.oauth2_unlink_success'))->flash();
        return redirect()->route('account');
    }
}
