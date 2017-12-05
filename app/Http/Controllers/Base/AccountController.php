<?php

namespace Pterodactyl\Http\Controllers\Base;

use Pterodactyl\Models\User;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;

class AccountController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * AccountController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag             $alert
     * @param \Pterodactyl\Services\Users\UserUpdateService $updateService
     */
    public function __construct(AlertsMessageBag $alert, UserUpdateService $updateService)
    {
        $this->alert = $alert;
        $this->updateService = $updateService;
    }

    /**
     * Display base account information page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('base.account');
    }

    /**
     * Update details for a user's account.
     *
     * @param \Pterodactyl\Http\Requests\Base\AccountDataFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function update(AccountDataFormRequest $request)
    {
        $data = [];
        if ($request->input('do_action') === 'password') {
            $data['password'] = $request->input('new_password');
        } elseif ($request->input('do_action') === 'email') {
            $data['email'] = $request->input('new_email');
        } elseif ($request->input('do_action') === 'identity') {
            $data = $request->only(['name_first', 'name_last', 'username']);
        }

        $this->updateService->setUserLevel(User::USER_LEVEL_USER);
        $this->updateService->handle($request->user(), $data);
        $this->alert->success(trans('base.account.details_updated'))->flash();

        return redirect()->route('account');
    }
}
