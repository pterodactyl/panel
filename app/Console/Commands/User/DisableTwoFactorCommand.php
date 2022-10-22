<?php

namespace Pterodactyl\Console\Commands\User;

use Illuminate\Console\Command;
use Pterodactyl\Models\User;

class DisableTwoFactorCommand extends Command
{
    protected $description = 'Disable two-factor authentication for a specific user in the Panel.';

    protected $signature = 'p:user:disable2fa {--email= : The email of the user to disable 2-Factor for.}';

    /**
     * DisableTwoFactorCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle()
    {
        if ($this->input->isInteractive()) {
            $this->output->warning(trans('command/messages.user.2fa_help_text'));
        }

        $email = $this->option('email') ?? $this->ask(trans('command/messages.user.ask_email'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->use_totp = false;
        $user->totp_secret = null;
        $user->save();

        $this->info(trans('command/messages.user.2fa_disabled', ['email' => $user->email]));
    }
}
