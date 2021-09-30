<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class WebauthnController extends AbstractLoginController
{
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    private CacheRepository $cache;

    private ValidationFactory $validation;

    public function __construct(CacheRepository $cache, ValidationFactory $validation)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->validation = $validation;
    }

    /**
     * @return JsonResponse|void
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function auth(Request $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
            return;
        }

        $details = $request->session()->get('auth_confirmation_token');
        if (!LoginCheckpointController::isValidSessionData($this->validation, $details)) {
            $this->sendFailedLoginResponse($request, null, LoginCheckpointController::TOKEN_EXPIRED_MESSAGE);
            return;
        }

        if (!hash_equals($request->input('confirmation_token') ?? '', $details['token_value'])) {
            $this->sendFailedLoginResponse($request);
            return;
        }

        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->findOrFail($details['user_id']);
        } catch (ModelNotFoundException $exception) {
            $this->sendFailedLoginResponse($request, null, LoginCheckpointController::TOKEN_EXPIRED_MESSAGE);
            return;
        }

        $this->auth->guard()->onceUsingId($user->id);

        try {
            $publicKey = $request->session()->pull(self::SESSION_PUBLICKEY_REQUEST);
            if (!$publicKey instanceof PublicKeyCredentialRequestOptions) {
                throw new ModelNotFoundException(trans('webauthn::errors.auth_data_not_found'));
            }

            $result = Webauthn::doAuthenticate(
                $user,
                $publicKey,
                $this->input($request, 'data'),
            );

            if (!$result) {
                return new JsonResponse([
                    'error' => [
                        'message' => 'Nice attempt, you didn\'t pass the challenge.',
                    ],
                ], JsonResponse::HTTP_I_AM_A_TEAPOT);
            }

            return $this->sendLoginResponse($user, $request);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], JsonResponse::HTTP_FORBIDDEN);
        }
    }

    /**
     * Retrieve the input with a string result.
     */
    private function input(Request $request, string $name, string $default = ''): string
    {
        $result = $request->input($name);

        return is_string($result) ? $result : $default;
    }
}
