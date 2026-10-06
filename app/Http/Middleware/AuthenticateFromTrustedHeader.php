<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Pterodactyl\Events\Auth\DirectLogin;
use Symfony\Component\HttpFoundation\IpUtils;
use Pterodactyl\Services\Users\UserCreationService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

/**
 * Authenticate a browser session from identity headers injected by a reverse
 * proxy (Authelia, Authentik, etc.). Disabled by default and only trusts headers
 * when the direct REMOTE_ADDR matches TRUSTED_PROXIES.
 */
class AuthenticateFromTrustedHeader
{
    public function __construct(
        private AuthManager $auth,
        private UserRepositoryInterface $users,
        private UserCreationService $userCreation,
    ) {
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        if (!config('auth.header.enabled') || $this->auth->guard()->check()) {
            return $next($request);
        }

        $usernameHeader = (string) config('auth.header.username_header');
        $emailHeader = (string) config('auth.header.email_header');
        $username = trim((string) $request->headers->get($usernameHeader, ''));
        $email = mb_strtolower(trim((string) $request->headers->get($emailHeader, '')));

        if ($username === '' && $email === '') {
            return $next($request);
        }

        if ($username === '' || $email === '') {
            throw new HttpException(400, 'Both remote authentication username and email headers are required.');
        }

        if (!$this->requestComesFromTrustedProxy($request)) {
            throw new HttpException(403, 'Remote authentication headers are only accepted from a trusted proxy.');
        }

        $user = $this->resolveUser($username, $email);

        if (!$user && !config('auth.header.auto_create')) {
            throw new HttpException(403, 'No local account matches the authenticated remote identity.');
        }

        if (!$user) {
            $user = $this->userCreation->handle([
                'username' => $username,
                'email' => $email,
                'name_first' => $username,
                'name_last' => 'User',
            ]);
        }

        $request->session()->forget('auth_confirmation_token');
        $request->session()->regenerate(true);

        $this->auth->guard()->login($user, false);
        event(new DirectLogin($user, false));

        return $next($request);
    }

    private function resolveUser(string $username, string $email): ?User
    {
        $byEmail = $this->find(['email' => $email]);
        if ($byEmail) {
            if (strcasecmp($byEmail->username, $username) !== 0) {
                throw new HttpException(409, 'The remote email is already associated with a different username.');
            }

            return $byEmail;
        }

        $byUsername = $this->find(['username' => $username]);
        if ($byUsername) {
            if (strcasecmp($byUsername->email, $email) !== 0) {
                throw new HttpException(409, 'The remote username is already associated with a different email address.');
            }

            return $byUsername;
        }

        return null;
    }

    private function find(array $where): ?User
    {
        try {
            $user = $this->users->findFirstWhere($where);
        } catch (RecordNotFoundException) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    private function requestComesFromTrustedProxy(Request $request): bool
    {
        $remoteAddress = $request->server->get('REMOTE_ADDR');
        $trusted = config('trustedproxy.proxies', []);

        if (!is_string($remoteAddress) || $remoteAddress === '' || empty($trusted)) {
            return false;
        }

        if (in_array($trusted, ['*', '**'], true)) {
            return true;
        }

        $proxies = array_values(array_filter(
            is_array($trusted) ? $trusted : [$trusted],
            static fn ($proxy) => is_string($proxy) && trim($proxy) !== '',
        ));

        return $proxies !== [] && IpUtils::checkIp($remoteAddress, $proxies);
    }
}
