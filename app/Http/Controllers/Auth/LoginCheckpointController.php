<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Pterodactyl\Models\User;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\SecurityKey;
use Illuminate\Contracts\Encryption\Encrypter;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginCheckpointController extends AbstractLoginController
{
    public const TOKEN_EXPIRED_MESSAGE = 'The authentication token provided has expired, please refresh the page and try again.';

    protected Encrypter $encrypter;

    protected Google2FA $google2FA;

    protected WebauthnServerRepository $repository;

    protected ValidationFactory $validation;

    /**
     * LoginCheckpointController constructor.
     */
    public function __construct(
        Encrypter $encrypter,
        Google2FA $google2FA,
        ValidationFactory $validation,
        WebauthnServerRepository $repository
    ) {
        parent::__construct();

        $this->encrypter = $encrypter;
        $this->google2FA = $google2FA;
        $this->validation = $validation;
        $this->repository = $repository;
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @return \Illuminate\Http\JsonResponse|void
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function token(LoginCheckpointRequest $request)
    {
        $user = $this->extractUserFromRequest($request);

        // Recovery tokens go through a slightly different pathway for usage.
        if (!is_null($recoveryToken = $request->input('recovery_token'))) {
            if ($this->isValidRecoveryToken($user, $recoveryToken)) {
                return $this->sendLoginResponse($user, $request);
            }
        } else {
            if (!$user->use_totp) {
                $this->sendFailedLoginResponse($request, $user);
            }

            $decrypted = $this->encrypter->decrypt($user->totp_secret);

            if ($this->google2FA->verifyKey($decrypted, (string) $request->input('authentication_code') ?? '', config('pterodactyl.auth.2fa.window'))) {
                return $this->sendLoginResponse($user, $request);
            }
        }

        $this->sendFailedLoginResponse($request, $user, !empty($recoveryToken) ? 'The recovery token provided is not valid.' : null);
    }

    /**
     * Authenticates a login request using a security key for a user.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function key(Request $request): JsonResponse
    {
        $key = $request->session()->get(SecurityKey::PK_SESSION_NAME);
        if (!$key instanceof PublicKeyCredentialRequestOptions) {
            throw new BadRequestHttpException('No security keys configured in session.');
        }

        $user = $this->extractUserFromRequest($request);

        $source = $this->repository->getServer($user)->loadAndCheckAssertionResponse(
            $request->input('data'),
            $key,
            $user->toPublicKeyCredentialEntity(),
            SecurityKey::getPsrRequestFactory($request)
        );

        if (hash_equals($user->uuid, $source->getUserHandle())) {
            return $this->sendLoginResponse($user, $request);
        }

        throw new BadRequestHttpException('An unexpected error was encountered while validating that security key.');
    }

    /**
     * Extracts the user from the session data using the provided confirmation token.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    protected function extractUserFromRequest(Request $request): User
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $details = $request->session()->get('auth_confirmation_token');
        if (!$this->hasValidSessionData($details)) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        if (!hash_equals($request->input('confirmation_token') ?? '', $details['token_value'])) {
            $this->sendFailedLoginResponse($request);
        }

        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->findOrFail($details['user_id']);
        } catch (ModelNotFoundException $exception) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        return $user;
    }

    /**
     * Determines if a given recovery token is valid for the user account. If we find a matching token
     * it will be deleted from the database.
     */
    protected function isValidRecoveryToken(User $user, string $value): bool
    {
        foreach ($user->recoveryTokens as $token) {
            if (password_verify($value, $token->token)) {
                $token->delete();

                return true;
            }
        }

        return false;
    }

    protected function hasValidSessionData(array $data): bool
    {
        return static::isValidSessionData($this->validation, $data);
    }

    /**
     * Determines if the data provided from the session is valid or not. This
     * will return false if the data is invalid, or if more time has passed than
     * was configured when the session was written.
     */
    protected static function isValidSessionData(ValidationFactory $validation, array $data): bool
    {
        $validator = $validation->make($data, [
            'user_id' => 'required|integer|min:1',
            'token_value' => 'required|string',
            'expires_at' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        if (!$data['expires_at'] instanceof CarbonInterface) {
            return false;
        }

        if ($data['expires_at']->isBefore(CarbonImmutable::now())) {
            return false;
        }

        return true;
    }
}
