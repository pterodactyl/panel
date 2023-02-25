<?php

namespace App\Console\Commands;

use App\Classes\Pterodactyl;
use App\Models\User;
use App\Traits\Referral;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MakeUserCommand extends Command
{
    use Referral;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user {--ptero_id=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin account with the Artisan Console';

    private Pterodactyl $pterodactyl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Pterodactyl $pterodactyl)
    {
        parent::__construct();
        $this->pterodactyl = $pterodactyl;
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ptero_id = $this->option('ptero_id') ?? $this->ask('Please specify your Pterodactyl ID.');
        $password = $this->secret('password') ?? $this->ask('Please specify your password.');

        // Validate user input
        $validator = Validator::make([
            'ptero_id' => $ptero_id,
            'password' => $password,
        ], [
            'ptero_id' => 'required|numeric|integer|min:1|max:2147483647',
            'password' => 'required|string|min:8|max:60',
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return 0;
        }

        //TODO: Do something with response (check for status code and give hints based upon that)
        $response = $this->pterodactyl->getUser($ptero_id);

        if (isset($response['errors'])) {
            if (isset($response['errors'][0]['code'])) {
                $this->error("code: {$response['errors'][0]['code']}");
            }
            if (isset($response['errors'][0]['status'])) {
                $this->error("status: {$response['errors'][0]['status']}");
            }
            if (isset($response['errors'][0]['detail'])) {
                $this->error("detail: {$response['errors'][0]['detail']}");
            }

            return 0;
        }
        $user = User::create([
            'name' => $response['first_name'],
            'email' => $response['email'],
            'role' => 'admin',
            'password' => Hash::make($password),
            'referral_code' => $this->createReferralCode(),
            'pterodactyl_id' => $response['id'],
        ]);

        $this->table(['Field', 'Value'], [
            ['ID', $user->id],
            ['Email', $user->email],
            ['Username', $user->name],
            ['Ptero-ID', $user->pterodactyl_id],
            ['Admin', $user->role],
            ['Referral code', $user->referral_code],
        ]);

        return 1;
    }
}
