<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Services\Users\TwoFactorSetupService;
use Pterodactyl\Services\Users\ToggleTwoFactorService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TwoFactorController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\Users\TwoFactorSetupService
     */
    private $setupService;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    private $validation;

    /**
     * @var \Pterodactyl\Services\Users\ToggleTwoFactorService
     */
    private $toggleTwoFactorService;

    /**
     * TwoFactorController constructor.
     */
    public function __construct(
        Factory $validation,
        TwoFactorSetupService $setupService,
        ToggleTwoFactorService $toggleTwoFactorService,
    ) {
        parent::__construct();

        $this->validation = $validation;
        $this->setupService = $setupService;
        $this->toggleTwoFactorService = $toggleTwoFactorService;
    }

    /**
     * Returns two-factor token credentials that allow a user to configure
     * it on their account. If two-factor is already enabled this endpoint
     * will return a 400 error.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request)
    {
        if ($request->user()->use_totp) {
            throw new BadRequestHttpException('Two-factor authentication is already enabled on this account.');
        }

        return new JsonResponse([
            'data' => $this->setupService->handle($request->user()),
        ]);
    }

    /**
     * Updates a user's account to have two-factor enabled.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid
     */
    public function store(Request $request)
    {
        $validator = $this->validation->make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $tokens = $this->toggleTwoFactorService->handle($request->user(), $request->input('code'), true);

        Activity::event('user:two-factor.create')->log();

        return new JsonResponse([
            'object' => 'recovery_tokens',
            'attributes' => [
                'tokens' => $tokens,
            ],
        ]);
    }

    /**
     * Disables two-factor authentication on an account if the password provided
     * is valid.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        if (!password_verify($request->input('password') ?? '', $request->user()->password)) {
            throw new BadRequestHttpException('The password provided was not valid.');
        }

        /** @var \Pterodactyl\Models\User $user */
        $user = $request->user();

        $user->update([
            'totp_authenticated_at' => Carbon::now(),
            'use_totp' => false,
        ]);

        Activity::event('user:two-factor.delete')->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
