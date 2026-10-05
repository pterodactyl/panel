<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Http\Requests\Api\Remote\SftpAuthenticationFormRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Traits\Helpers\ThrottlesLogins;
use Pterodactyl\Validation\UserSSHKeyRules;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SftpAuthenticationController extends Controller
{
    use ThrottlesLogins;

    /**
     * Authenticate a set of credentials and return the associated server details
     * for a SFTP connection on the daemon. This supports both public key and password
     * based credentials.
     */
    public function __invoke(SftpAuthenticationFormRequest $request, GetUserPermissionsService $permissions): JsonResponse
    {
        $connection = $this->parseUsername(JsonValueGuard::string($request->validated('username')));
        throw_if(empty($connection['server']), BadRequestHttpException::class, 'No valid server identifier was included in the request.');

        if ($this->hasTooManyLoginAttempts($request)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));

            throw new TooManyRequestsHttpException($seconds, "Too many login attempts for this account, please try again in $seconds seconds.");
        }

        $user = $this->getUser($request, $connection['username']);
        $server = $this->getServer($request, $connection['server']);

        if ($request->validated('type') !== 'public_key') {
            if (! Hash::check(JsonValueGuard::string($request->validated('password')), $user->password)) {
                Activity::event('auth:sftp.fail')->property('method', 'password')->subject($user)->log();

                $this->reject($request);
            }
        } else {
            $key = null;
            $publicKey = mb_trim(JsonValueGuard::string($request->validated('password')));
            if (UserSSHKeyRules::isSupportedPublicKey($publicKey)) {
                try {
                    $key = PublicKeyLoader::loadPublicKey($publicKey);
                } catch (NoKeyLoadedException) {
                    // do nothing
                }
            }

            if (! $key || ! $user->sshKeys()->where('fingerprint', $key->getFingerprint('sha256'))->exists()) {
                // Don't log public key failures - this endpoint is hit once for every key the
                // user offers, so only the (rarer, more meaningful) bad-password failures are logged.
                $this->reject($request, ($key) === null);
            }
        }

        $this->validateSftpAccess($user, $server, $permissions);

        return new JsonResponse([
            'user' => $user->uuid,
            'server' => $server->uuid,
            'permissions' => $permissions->handle($server, $user),
        ]);
    }

    /**
     * Finds the server being requested and ensures that it belongs to the node this
     * request stems from.
     */
    protected function getServer(Request $request, string $uuid): Server
    {
        $server = Server::query()
            ->where(fn (Builder $builder) => $builder->where('uuid', $uuid)->orWhere('uuidShort', $uuid))
            ->where('node_id', RemoteRequestNode::get($request)->id)
            ->first();

        return $server ?? $this->reject($request);
    }

    /**
     * Finds a user with the given username or increments the login attempts.
     */
    protected function getUser(Request $request, string $username): User
    {
        $user = User::query()->where('username', $username)->first();

        return $user ?? $this->reject($request);
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
            'username' => strrev($parts[1] ?? ''),
            'server' => strrev($parts[0]),
        ];
    }

    /**
     * Rejects the request and increments the login attempts.
     */
    protected function reject(Request $request, bool $increment = true): never
    {
        if ($increment) {
            $this->incrementLoginAttempts($request);
        }

        throw new HttpForbiddenException('Authorization credentials were not correct, please try again.');
    }

    /**
     * Validates that a user should have permission to use SFTP for the given server.
     */
    protected function validateSftpAccess(User $user, Server $server, GetUserPermissionsService $permissions): void
    {
        if (! $user->root_admin && $server->owner_id !== $user->id) {
            $resolved = $permissions->handle($server, $user);

            if (! in_array(Permissions::FileSftp->value, $resolved)) {
                Activity::event('server:sftp.denied')->actor($user)->subject($server)->log();

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
        $raw = JsonValueGuard::nullableString($request->input('username', '')) ?? '';
        $username = explode('.', strrev($raw));

        return mb_strtolower(strrev($username[0] ?? '').'|'.$request->ip()); // @phpstan-ignore nullCoalesce.offset
    }
}
