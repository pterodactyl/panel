<?php

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
        $totpData = $this->twoFactorSetupService->handle($request->user());

        return response()->json([
            'qrImage' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $totpData,
        ]);
    }

    /**
     * Verifies that 2FA token received is valid and will work on the account.
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
            $this->toggleTwoFactorService->handle($request->user(), $request->input('token') ?? '');

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
            $this->toggleTwoFactorService->handle($request->user(), $request->input('token') ?? '', false);
        } catch (TwoFactorAuthenticationTokenInvalid $exception) {
            $this->alert->danger(trans('base.security.2fa_disable_error'))->flash();
        }

        return redirect()->route('account.security');
    }

    /**
     * Revokes a user session.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revoke(Request $request, string $id)
    {
        $this->repository->deleteUserSession($request->user()->id, $id);

        return redirect()->route('account.security');
    }
}
