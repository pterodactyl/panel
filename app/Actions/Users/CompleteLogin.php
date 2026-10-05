<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Pterodactyl\Contracts\Users\CompletesLogins;
use Pterodactyl\Data\LoginCheckpoint;
use Pterodactyl\Data\LoginResult;
use Pterodactyl\Events\Auth\DirectLogin;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\User;

final readonly class CompleteLogin implements CompletesLogins
{
    private const string CHECKPOINT_KEY = 'auth_confirmation_token';

    private const string INTENDED = '/';

    public function complete(User $user): LoginResult
    {
        if (! $user->use_totp) {
            return $this->establish($user);
        }

        Activity::event('auth:checkpoint')->withRequestMetadata()->subject($user)->log();

        $checkpoint = LoginCheckpoint::issue($user->id);
        Session::put(self::CHECKPOINT_KEY, $checkpoint);

        return LoginResult::checkpoint($user, $checkpoint, self::INTENDED);
    }

    public function establish(User $user): LoginResult
    {
        Session::remove(self::CHECKPOINT_KEY);
        Session::regenerate();

        Auth::guard()->login($user, true);

        Event::dispatch(new DirectLogin($user, true));

        return LoginResult::established($user, self::INTENDED);
    }

    public function pendingCheckpoint(): ?LoginCheckpoint
    {
        $checkpoint = Session::get(self::CHECKPOINT_KEY);

        return LoginCheckpoint::isPending($checkpoint) ? $checkpoint : null;
    }
}
