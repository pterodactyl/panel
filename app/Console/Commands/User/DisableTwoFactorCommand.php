<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\User;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Models\User;

#[Description('Disable two-factor authentication for a specific user in the Panel.')]
#[Signature('p:user:disable2fa {--email= : The email of the user to disable 2-Factor for.}')]
class DisableTwoFactorCommand extends Command
{
    /**
     * Handle command execution process.
     */
    public function handle(): void
    {
        if ($this->input->isInteractive()) {
            $this->output->warning(trans('command/messages.user.2fa_help_text'));
        }

        $email = $this->option('email') ?? $this->ask(trans('command/messages.user.ask_email'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->update([
            'use_totp' => false,
            'totp_secret' => null,
        ]);

        $this->info(trans('command/messages.user.2fa_disabled', ['email' => $user->email]));
    }
}
