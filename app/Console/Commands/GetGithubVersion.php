<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GetGithubVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cp:versioncheck:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the latest Version from Github';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            $latestVersion = Http::get('https://api.github.com/repos/controlpanel-gg/dashboard/tags')->json()[0]['name'];
            Storage::disk('local')->put('latestVersion', $latestVersion);
        } catch (Exception $e) {
            Storage::disk('local')->put('latestVersion', "unknown");
            Log::error($e);
        }
        return Command::SUCCESS;
    }
}
