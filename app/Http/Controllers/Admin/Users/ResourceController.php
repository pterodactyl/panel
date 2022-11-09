<?php

namespace Pterodactyl\Http\Controllers\Admin\Users;

use Illuminate\View\View;
use Pterodactyl\Models\User;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Requests\Admin\Users\ResourceFormRequest;

class ResourceController extends Controller
{
    /**
     * UserController constructor.
     */
    public function __construct(private AlertsMessageBag $alert)
    {
    }

    /**
     * Display user resource page.
     */
    public function view(User $user): View
    {
        return view('admin.users.resources', ['user' => $user]);
    }

    /**
     * Update a user's resource balances.
     *
     * @throws DataValidationException
     * @throws RecordNotFoundException
     */
    public function update(ResourceFormRequest $request, User $user): RedirectResponse
    {
        // TODO: use userUpdateService in future.
        $user->update([
            'store_balance' => $request->input('store_balance'),
            'store_cpu' => $request->input('store_cpu'),
            'store_memory' => $request->input('store_memory'),
            'store_disk' => $request->input('store_disk'),
            'store_slots' => $request->input('store_slots'),
            'store_ports' => $request->input('store_ports'),
            'store_backups' => $request->input('store_backups'),
            'store_databases' => $request->input('store_databases'),
        ]);

        $this->alert->success('User resources have been updated.')->flash();

        return redirect()->route('admin.users.resources', $user->id);
    }
}
