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

use Illuminate\Http\Request;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SessionRepositoryInterface;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;

class SecurityController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\SessionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\ToggleTwoFactorService
     */
    protected $toggleTwoFactorService;

    /**
     * @var \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    protected $twoFactorSetupService;

    /**
     * SecurityController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                            $alert
     * @param \Illuminate\Contracts\Config\Repository                      $config
     * @param \Pterodactyl\Contracts\Repository\SessionRepositoryInterface $repository
     * @param \Pterodactyl\Services\Users\ToggleTwoFactorService           $toggleTwoFactorService
     * @param \Pterodactyl\Services\Users\TwoFactorSetupService            $twoFactorSetupService
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        SessionRepositoryInterface $repository,
        ToggleTwoFactorService $toggleTwoFactorService,
        TwoFactorSetupService $twoFactorSetupService
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->repository = $repository;
        $this->toggleTwoFactorService = $toggleTwoFactorService;
        $this->twoFactorSetupService = $twoFactorSetupService;
    }

    /**
     * Returns Security Management Page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($this->config->get('session.driver') === 'database') {
            $activeSessions = $this->repository->getUserSessions($request->user()->id);
        }

        return view('base.security', [
            'sessions' => $activeSessions ?? null,
        ]);
    }

    /**
     * Generates TOTP Secret and returns popup data for user to verify
     * that they can generate a valid response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function generateTotp(Request $request)
    {
        return response()->json([
            'qrImage' => $this->twoFactorSetupService->handle($request->user()),
        ]);
    }

    /**
     * Verifies that 2FA token recieved is valid and will work on the account.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function setTotp(Request $request)
    {
        try {
            $this->toggleTwoFactorService->handle($request->user(), $request->input('token'));

            return response('true');
        } catch (TwoFactorAuthenticationTokenInvalid $exception) {
            return response('false');
        }
    }

    /**
     * Disables TOTP on an account.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function disableTotp(Request $request)
    {
        try {
            $this->toggleTwoFactorService->handle($request->user(), $request->input('token'), false);
        } catch (TwoFactorAuthenticationTokenInvalid $exception) {
            $this->alert->danger(trans('base.security.2fa_disable_error'))->flash();
        }

        return redirect()->route('account.security');
    }

    /**
     * Revokes a user session.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revoke(Request $request, $id)
    {
        $this->repository->deleteUserSession($request->user()->id, $id);

        return redirect()->route('account.security');
    }
}
