<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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

namespace Pterodactyl\Http\Controllers\Base;

use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Base\AccountDataFormRequest;
use Pterodactyl\Services\Users\UserUpdateService;

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
    public function __construct(
        AlertsMessageBag $alert,
        UserUpdateService $updateService
    ) {
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

        $this->updateService->handle($request->user()->id, $data);
        $this->alert->success(trans('base.account.details_updated'))->flash();

        return redirect()->route('account');
    }
}
