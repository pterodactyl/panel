<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportUsersFromPteroCommand extends Command
{
    /**
     * @var string
     */
    private $importFileName = 'users.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {--initial_credits=} {--initial_server_limit=} {--confirm=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return bool
     */
    public function handle()
    {

        //check if json file exists
        if (! Storage::disk('local')->exists('users.json')) {
            $this->error('[ERROR] '.storage_path('app').'/'.$this->importFileName.' is missing');

            return false;
        }

        //check if json file is valid
        $json = json_decode(Storage::disk('local')->get('users.json'));
        if (! array_key_exists(2, $json)) {
            $this->error('[ERROR] Invalid json file');

            return false;
        }
        if (! $json[2]->data) {
            $this->error('[ERROR] Invalid json file / No users found!');

            return false;
        }

        //ask questions :)
        $initial_credits = $this->option('initial_credits') ?? $this->ask('Please specify the amount of starting credits users should get. ');
        $initial_server_limit = $this->option('initial_server_limit') ?? $this->ask('Please specify the initial server limit users should get.');
        $confirm = strtolower($this->option('confirm') ?? $this->ask('[y/n] Are you sure you want to remove all existing users from the database continue importing?'));

        //cancel
        if ($confirm !== 'y') {
            $this->error('[ERROR] Stopped import script!');

            return false;
        }

        //import users
        $this->deleteCurrentUserBase();
        $this->importUsingJsonFile($json, $initial_credits, $initial_server_limit);

        return true;
    }

    /**
     * @return void
     */
    private function deleteCurrentUserBase()
    {
        $currentUserCount = User::count();
        if ($currentUserCount == 0) {
            return;
        }

        $this->line("Deleting ({$currentUserCount}) users..");
        foreach (User::all() as $user) {
            $user->delete();
        }
    }

    /**
     * @param $json
     * @param $initial_credits
     * @param $initial_server_limit
     * @return void
     */
    private function importUsingJsonFile($json, $initial_credits, $initial_server_limit)
    {
        $this->withProgressBar($json[2]->data, function ($user) use ($initial_server_limit, $initial_credits) {
            $role = $user->root_admin == '0' ? 'member' : 'admin';

            User::create([
                'pterodactyl_id' => $user->id,
                'name' => $user->name_first,
                'email' => $user->email,
                'password' => $user->password,
                'role' => $role,
                'credits' => $initial_credits,
                'server_limit' => $initial_server_limit,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        });

        $this->newLine();
        $this->line('Done importing, you can now login using your pterodactyl credentials.');
        $this->newLine();
    }
}
