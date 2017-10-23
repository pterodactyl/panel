<?php

namespace Pterodactyl\Http\Controllers\API\Remote;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Services\Sftp\AuthenticateUsingPasswordService;
use Pterodactyl\Http\Requests\API\Remote\SftpAuthenticationFormRequest;

class SftpController extends Controller
{
    use ThrottlesLogins;

    /**
     * @var \Pterodactyl\Services\Sftp\AuthenticateUsingPasswordService
     */
    private $authenticationService;

    /**
     * SftpController constructor.
     *
     * @param \Pterodactyl\Services\Sftp\AuthenticateUsingPasswordService $authenticationService
     */
    public function __construct(AuthenticateUsingPasswordService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * Authenticate a set of credentials and return the associated server details
     * for a SFTP connections on the daemon.
     *
     * @param \Pterodactyl\Http\Requests\API\Remote\SftpAuthenticationFormRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function index(SftpAuthenticationFormRequest $request): JsonResponse
    {
        $connection = explode('.', $request->input('username'));
        $this->incrementLoginAttempts($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            return response()->json([
                'error' => 'Logins throttled.',
            ], 429);
        }

        try {
            $data = $this->authenticationService->handle(
                array_get($connection, 0),
                $request->input('password'),
                object_get($request->attributes->get('node'), 'id', 0),
                array_get($connection, 1)
            );

            $this->clearLoginAttempts($request);
        } catch (AuthenticationException $exception) {
            return response()->json([
                'error' => 'Invalid credentials.',
            ], 403);
        } catch (RecordNotFoundException $exception) {
            return response()->json([
                'error' => 'Invalid server.',
            ], 404);
        }

        return response()->json($data);
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return Str::lower(array_get(explode('.', $request->input('username')), 0) . '|' . $request->ip());
    }
}
