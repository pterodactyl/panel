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

class AuthenticateFromHeader
{
    public function __construct(
        private AuthManager $auth,
        private UserRepositoryInterface $repository,
        private UserCreationService $creationService,
    ) {
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        if (!config('auth.header.enabled') || $this->auth->guard()->check()) {
            return $next($request);
        }
        $username = trim((string) $request->headers->get(config('auth.header.username_header')));
        $email = mb_strtolower(trim((string) $request->headers->get(config('auth.header.email_header'))));

        if ($username === '' && $email === '') {
            return $next($request);
        }

        if ($username === '' || $email === '') {
            throw new HttpException(400, 'Both remote authentication headers must be provided.');
        }

        if (!$this->isTrustedProxy($request)) {
            throw new HttpException(403, 'Remote authentication headers were not provided by a trusted proxy.');
        }

        $user = $this->findUser(['email' => $email]);

        if (!$user) {
            $user = $this->findUser(['username' => $username]);

            if ($user && strcasecmp($user->email, $email) !== 0) {
                throw new HttpException(409, 'The remote username is already associated with another email address.');
            }
        }

        if (!$user && !config('auth.header.auto_create')) {
            throw new HttpException(403, 'No local account exists for the authenticated remote user.');
        }
        if (!$user) {
            $user = $this->creationService->handle([
                'username' => $username,
                'email' => $email,
                'name_first' => $username,
                'name_last' => 'User',
            ]);
        }

        $request->session()->remove('auth_confirmation_token');
        $request->session()->regenerate();

        $this->auth->guard()->login($user);
        event(new DirectLogin($user, false));

        return $next($request);
    }

    private function findUser(array $fields): ?User
    {
        try {
            $user = $this->repository->findFirstWhere($fields);
        } catch (RecordNotFoundException) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    private function isTrustedProxy(Request $request): bool
    {
        $remoteAddress = $request->server->get('REMOTE_ADDR');
        $trustedProxies = config('trustedproxy.proxies', []);

        if (!$remoteAddress || empty($trustedProxies)) {
            return false;
        }

        if (in_array($trustedProxies, ['*', '**'], true)) {
            return true;
        }

        $trustedProxies = array_values(array_filter(
            is_array($trustedProxies) ? $trustedProxies : [$trustedProxies],
            fn ($proxy) => is_string($proxy) && trim($proxy) !== '',
        ));

        return $trustedProxies !== [] && IpUtils::checkIp($remoteAddress, $trustedProxies);
    }
}
