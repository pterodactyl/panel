<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\User;
use App\Notifications\ServerCreationError;
use Illuminate\Console\Command;

class notify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:user {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test notifications to this user';

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
     * @param  int  $id
     * @return int
     */
    public function handle()
    {
        User::findOrFail($this->argument('id'))->notify(new ServerCreationError(Server::all()[0]));

        return 'message send';
    }
}
