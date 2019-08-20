<?php

namespace App\Http\Controllers\Base;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Session\Session;
use App\Http\Controllers\Controller;
use App\Services\Users\UserUpdateService;
use App\Traits\Helpers\AvailableLanguages;
use App\Http\Requests\Base\AccountDataFormRequest;

class AccountController extends Controller
{
    use AvailableLanguages;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Auth\SessionGuard
     */
    protected $sessionGuard;

    /**
     * @var \App\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * AccountController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag             $alert
     * @param \Illuminate\Auth\AuthManager                  $authManager
     * @param \App\Services\Users\UserUpdateService $updateService
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
        return view('base.account', [
            'languages' => $this->getAvailableLanguages(true),
        ]);
    }

    /**
     * Update details for a user's account.
     *
     * @param \App\Http\Requests\Base\AccountDataFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
                $data = $request->only(['name_first', 'name_last', 'username', 'language']);
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
