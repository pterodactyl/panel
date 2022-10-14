<?php

namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Illuminate\View\View;
use Pterodactyl\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Jexactyl\ApprovalFormRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ApprovalsController extends Controller
{
    /**
     * ApprovalsController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    /**
     * Render the Jexactyl referrals interface.
     */
    public function index(): View
    {
        $users = User::where('approved', false)->get();

        return view('admin.jexactyl.approvals', [
            'enabled' => $this->settings->get('jexactyl::approvals:enabled', false),
            'webhook' => $this->settings->get('jexactyl::approvals:webhook'),
            'users' => $users,
        ]);
    }

    /**
     * Updates the settings for approvals.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ApprovalFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('jexactyl::approvals:' . $key, $value);
        }

        $this->alert->success('Jexactyl Approval settings have been updated.')->flash();
        return redirect()->route('admin.jexactyl.approvals');
    }

    /**
     * Approve all users currently waiting to be approved.
     */
    public function approveAll(Request $request): RedirectResponse
    {
      User::where('approved', false)->update(['approved', true]);

      $this->alert->success('All users have been approved successfully.')->flash();
      return redirect()->route('admin.jexactyl.approvals');
    }

    /**
     * Approve an incoming approval request.
     */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $user = User::where('id', $id)->first();
        $user->update(['approved' => true]);
        // This gives the user access to the frontend.

        $this->alert->success($user->username . ' has been approved.')->flash();
        return redirect()->route('admin.jexactyl.approvals');
    }

    /**
     * Deny an incoming approval request.
     */
    public function deny(Request $request, int $id): RedirectResponse
    {
        $user = User::where('id', $id)->first();
        $user->delete();
        // While typically we should look for associated servers, there
        // shouldn't be any present - as the user has been waiting for approval.

        $this->alert->success($user->username . ' has been denied.')->flash();
        return redirect()->route('admin.jexactyl.approvals');
    }
}
