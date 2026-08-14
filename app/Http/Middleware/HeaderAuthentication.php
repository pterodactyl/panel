<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HeaderAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('auth.header.enabled', false)) {
            return $next($request);
        }

        $usernameHeader = config('auth.header.username_header', 'X-Auth-Username');
        $emailHeader = config('auth.header.email_header', 'X-Auth-Email');

        $username = $request->header($usernameHeader);
        $email = $request->header($emailHeader);

        if (!$username || !$email) {
            return $next($request);
        }

        $user = User::where('email', $email)->first();

        if (!$user && config('auth.header.auto_create', false)) {
            $user = new User();
            $user->uuid = Uuid::uuid4()->toString();
            $user->username = $username;
            $user->email = $email;
            $user->name_first = $username;
            $user->name_last = $username;
            $user->password = bcrypt(Uuid::uuid4()->toString());
            $user->language = config('app.locale', 'en');
            $user->root_admin = false;
            $user->use_totp = false;
            $user->totp_secret = null;
            $user->external_id = '';
            $user->gravatar = true;
            $user->totp_authenticated_at = null;
            $user->save();
        }

        if ($user) {
            Auth::login($user);
        }

        return $next($request);
    }
} 