<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Rules\Username;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Pterodactyl\Services\Users\UserCreationService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Authenticates requests via a trusted HTTP header set by an authenticating
 * reverse proxy (e.g. Authelia, Authentik, oauth2-proxy). If the configured
 * header is present the user is looked up by username — and optionally
 * auto-created — then logged in for the session.
 *
 * SECURITY: only enable this when the application is exclusively reachable
 * through the authenticating proxy. If the panel is directly exposed, any
 * client could forge the header and authenticate as any user.
 */
class RemoteUserAuthenticate
{
    public function __construct(
        private Guard $guard,
        private UserCreationService $creationService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('pterodactyl.auth.remote_user.enabled') || $this->guard->check()) {
            return $next($request);
        }

        $username = trim((string) $request->header((string) config('pterodactyl.auth.remote_user.user_header'), ''));
        if ($username === '') {
            return $next($request);
        }

        $user = User::query()->where('username', mb_strtolower($username))->first();
        if (is_null($user)) {
            if (!config('pterodactyl.auth.remote_user.create_users', true)) {
                throw new HttpException(403, 'Remote user authentication denied: account does not exist.');
            }

            $user = $this->createUser($request, $username);
        }

        $this->guard->login($user, true);

        return $next($request);
    }

    /**
     * Create a new user from the trusted proxy headers.
     *
     * @throws HttpException when the header values cannot yield a valid account
     */
    private function createUser(Request $request, string $username): User
    {
        $email = trim((string) $request->header((string) config('pterodactyl.auth.remote_user.email_header'), ''));

        // Many proxies send a full email as the "user" header — use the local
        // part when the raw value is not a valid panel username.
        if (!preg_match(Username::VALIDATION_REGEX, mb_strtolower($username))) {
            $emailLocal = $email !== '' ? (strstr($email, '@', true) ?: $email) : '';
            $candidate = mb_strtolower((string) (strstr($username, '@', true) ?: $emailLocal));
            $candidate = is_string($candidate) ? preg_replace('/[^a-z0-9._-]/', '', $candidate) : '';
            if (!$candidate || !preg_match(Username::VALIDATION_REGEX, $candidate)) {
                throw new HttpException(403, 'Remote user header did not provide a usable username.');
            }
            $username = $candidate;
        }

        if ($email === '') {
            $email = sprintf('%s@%s', $username, $request->getHost());
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new HttpException(403, 'Remote user email is already bound to a different account.');
        }

        try {
            return $this->creationService->handle([
                'username' => mb_strtolower($username),
                'email' => $email,
                'root_admin' => false,
            ]);
        } catch (\Exception $exception) {
            Log::warning('Remote user account creation failed.', ['exception' => $exception]);

            throw new HttpException(403, 'Remote user account creation failed.');
        }
    }
}
