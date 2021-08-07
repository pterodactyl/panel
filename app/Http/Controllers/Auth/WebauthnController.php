<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class WebauthnController extends AbstractLoginController
{
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    private CacheRepository $cache;

    public function __construct(AuthManager $auth, ConfigRepository $config, CacheRepository $cache)
    {
        parent::__construct($auth, $config);

        $this->cache = $cache;
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

        $token = $request->input('confirmation_token');
        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->findOrFail($this->cache->get($token, 0));
        } catch (ModelNotFoundException $exception) {
            $this->incrementLoginAttempts($request);

            $this->sendFailedLoginResponse(
                $request,
                null,
                'The authentication token provided has expired, please refresh the page and try again.'
            );

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

            $this->cache->delete($token);

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
