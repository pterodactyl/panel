<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Repositories\UserRepository;

class MakeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pterodactyl:user
                            {--firstname= : First name to use for this account.}
                            {--lastname= : Last name to use for this account.}
                            {--username= : Username to use for this account.}
                            {--email= : Email address to use for this account.}
                            {--password= : Password to assign to the user.}
                            {--admin= :  Boolean flag for if user should be an admin.}';

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
        $data['name_first'] = is_null($this->option('firstname')) ? $this->ask('First Name') : $this->option('firstname');
        $data['name_last'] = is_null($this->option('lastname')) ? $this->ask('Last Name') : $this->option('lastname');
        $data['username'] = is_null($this->option('username')) ? $this->ask('Username') : $this->option('username');
        $data['email'] = is_null($this->option('email')) ? $this->ask('Email') : $this->option('email');
        $data['password'] = is_null($this->option('password')) ? $this->secret('Password') : $this->option('password');
        $password_confirmation = is_null($this->option('password')) ? $this->secret('Confirm Password') : $this->option('password');

        if ($data['password'] !== $password_confirmation) {
            return $this->error('The passwords provided did not match!');
        }

        $data['root_admin'] = is_null($this->option('admin')) ? $this->confirm('Is this user a root administrator?') : $this->option('admin');
        
        try {
            $user = new UserRepository;
            $user->create($data);

            return $this->info('User successfully created.');
        } catch (\Exception $ex) {
            return $this->error($ex->getMessage());
        }
    }
}