<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\Auth\DirectLogin;

class AuthenticateHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        // Check if header authentication is enabled
        if (!config('auth.header.enabled', false)) {
            return $next($request);
        }

        // Skip if already authenticated
        if (Auth::check()) {
            return $next($request);
        }

        // Get header names from config
        $usernameHeader = config('auth.header.username_header', 'X-Auth-Username');
        $emailHeader = config('auth.header.email_header', 'X-Auth-Email');

        // Check if required headers are present
        $username = $request->header($usernameHeader);
        $email = $request->header($emailHeader);

        if (empty($username) && empty($email)) {
            return $next($request);
        }

        // Try to find existing user by username or email
        $user = null;
        if (!empty($username)) {
            $user = User::where('username', $username)->first();
        }

        if (!$user && !empty($email)) {
            $user = User::where('email', $email)->first();
        }

        // Auto-create user if enabled and user not found
        if (!$user && config('auth.header.auto_create_user', false)) {
            if (!empty($username) && !empty($email)) {
                $user = $this->createUser($username, $email);
            }
        }

        // Authenticate user if found
        if ($user) {
            Auth::login($user, true);
            Event::dispatch(new DirectLogin($user, true));
            Log::info('User authenticated via HTTP header', ['user_id' => $user->id, 'username' => $user->username]);
        }

        return $next($request);
    }

    /**
     * Create a new user from header credentials.
     *
     * @param  string  $username
     * @param  string  $email
     * @return \Pterodactyl\Models\User
     */
    protected function createUser(string $username, string $email): User
    {
        $user = User::create([
            'uuid' => Str::uuid()->toString(),
            'username' => $username,
            'email' => $email,
            'name_first' => $username,
            'name_last' => 'User',
            'password' => Hash::make(Str::random(32)),
            'language' => config('app.locale', 'en'),
        ]);

        Log::info('User auto-created via HTTP header', ['user_id' => $user->id, 'username' => $user->username]);

        return $user;
    }
}