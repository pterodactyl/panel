<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Services\Sftp\AuthenticateUsingPasswordService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\SftpAuthenticationFormRequest;

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
     * for a SFTP connection on the daemon.
     *
     * @param \Pterodactyl\Http\Requests\Api\Remote\SftpAuthenticationFormRequest $request
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
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $data = $this->authenticationService->handle(
                array_get($connection, 0),
                $request->input('password'),
                object_get($request->attributes->get('node'), 'id', 0),
                array_get($connection, 1)
            );

            $this->clearLoginAttempts($request);
        } catch (BadRequestHttpException $exception) {
            return response()->json([
                'error' => 'The server you are trying to access is not installed or is suspended.',
            ], Response::HTTP_BAD_REQUEST);
        } catch (RecordNotFoundException $exception) {
            return response()->json([
                'error' => 'Unable to locate a resource using the username and password provided.',
            ], Response::HTTP_NOT_FOUND);
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
        return strtolower(array_get(explode('.', $request->input('username')), 0) . '|' . $request->ip());
    }
}
