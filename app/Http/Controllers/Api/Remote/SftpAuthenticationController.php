<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Permission;
use phpseclib3\Crypt\PublicKeyLoader;
use Pterodactyl\Http\Controllers\Controller;
use phpseclib3\Exception\NoKeyLoadedException;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Remote\SftpAuthenticationFormRequest;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SftpAuthenticationController extends Controller
{
    use ThrottlesLogins;

    protected GetUserPermissionsService $permissions;

    public function __construct(GetUserPermissionsService $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Authenticate a set of credentials and return the associated server details
     * for a SFTP connection on the daemon. This supports both public key and password
     * based credentials.
     */
    public function __invoke(SftpAuthenticationFormRequest $request): JsonResponse
    {
        $connection = $this->parseUsername($request->input('username'));
        if (empty($connection['server'])) {
            throw new BadRequestHttpException('No valid server identifier was included in the request.');
        }

        if ($this->hasTooManyLoginAttempts($request)) {
            $seconds = $this->limiter()->availableIn($this->throttleKey($request));

            throw new TooManyRequestsHttpException($seconds, "Too many login attempts for this account, please try again in {$seconds} seconds.");
        }

        $user = $this->getUser($request, $connection['username']);
        $server = $this->getServer($request, $connection['server']);

        if ($request->input('type') !== 'public_key') {
            if (!password_verify($request->input('password'), $user->password)) {
                $this->reject($request);
            }
        } else {
            $key = null;
            try {
                $key = PublicKeyLoader::loadPublicKey(trim($request->input('password')));
            } catch (NoKeyLoadedException $exception) {
                // do nothing
            }

            if (!$key || !$user->sshKeys()->where('fingerprint', $key->getFingerprint('sha256'))->exists()) {
                $this->reject($request, is_null($key));
            }
        }

        $this->validateSftpAccess($user, $server);

        return new JsonResponse([
            'server' => $server->uuid,
            'permissions' => $this->permissions->handle($server, $user),
        ]);
    }

    /**
     * Finds the server being requested and ensures that it belongs to the node this
     * request stems from.
     */
    protected function getServer(Request $request, string $uuid): Server
    {
        return Server::query()
            ->where(fn ($builder) => $builder->where('uuid', $uuid)->orWhere('uuidShort', $uuid))
            ->where('node_id', $request->attributes->get('node')->id)
            ->firstOr(function () use ($request) {
                $this->reject($request);
            });
    }

    /**
     * Finds a user with the given username or increments the login attempts.
     */
    protected function getUser(Request $request, string $username): User
    {
        return User::query()->where('username', $username)->firstOr(function () use ($request) {
            $this->reject($request);
        });
    }

    /**
     * Parses the username provided to the request.
     *
     * @return array{"username": string, "server": string}
     */
    protected function parseUsername(string $value): array
    {
        // Reverse the string to avoid issues with usernames that contain periods.
        $parts = explode('.', strrev($value), 2);

        // Unreverse the strings after parsing them apart.
        return [
            'username' => strrev(array_get($parts, 1)),
            'server' => strrev(array_get($parts, 0)),
        ];
    }

    /**
     * Rejects the request and increments the login attempts.
     */
    protected function reject(Request $request, bool $increment = true): void
    {
        if ($increment) {
            $this->incrementLoginAttempts($request);
        }

        throw new HttpForbiddenException('Authorization credentials were not correct, please try again.');
    }

    /**
     * Validates that a user should have permission to use SFTP for the given server.
     */
    protected function validateSftpAccess(User $user, Server $server): void
    {
        if (!$user->root_admin && $server->owner_id !== $user->id) {
            $permissions = $this->permissions->handle($server, $user);

            if (!in_array(Permission::ACTION_FILE_SFTP, $permissions)) {
                throw new HttpForbiddenException('You do not have permission to access SFTP for this server.');
            }
        }

        $server->validateCurrentState();
    }

    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        $username = explode('.', strrev($request->input('username', '')));

        return strtolower(strrev($username[0] ?? '') . '|' . $request->ip());
    }
}
