<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\Controllers\Auth\PasswordResetActivityTest;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Http\HttpTestCase;

uses(HttpTestCase::class);

test('completing a password reset logs a translatable auth event', function () {
    $user = User::factory()->create();

    Event::dispatch(new PasswordReset($user));

    $log = ActivityLog::query()->where('event', 'auth:password-reset')->latest('id')->firstOrFail();
    expect($log->subjects->first()->subject_id)->toBe($user->id);
    expect(trans('activity.'.str_replace(':', '.', $log->event)))->toBe('Password reset');
});
