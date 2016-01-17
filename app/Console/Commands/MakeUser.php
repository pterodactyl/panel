<?php

namespace Pterodactyl\Console\Commands;

use Hash;
use Illuminate\Console\Command;

use Pterodactyl\Repositories\UserRepository;

class MakeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user within the panel.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->ask('Email');
        $password = $this->secret('Password');
        $password_confirmation = $this->secret('Confirm Password');

        if ($password !== $password_confirmation) {
            return $this->error('The passwords provided did not match!');
        }

        $admin = $this->confirm('Is this user a root administrator?');

        try {
            $user = new UserRepository;
            $user->create($email, $password, $admin);
            return $this->info('User successfully created.');
        } catch (\Exception $ex) {
            return $this->error($ex->getMessage());
        }
    }
}
