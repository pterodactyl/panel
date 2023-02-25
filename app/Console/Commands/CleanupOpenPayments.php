<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class CleanupOpenPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:open:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all payments from the database that have state "open"';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // delete all payments that have state "open" and are older than 1 hour
        try {
            Payment::where('status', 'open')->where('updated_at', '<', now()->subHour())->delete();
        } catch (\Exception $e) {
            $this->error('Could not delete payments: ' . $e->getMessage());
            return 1;
        }

        $this->info('Successfully deleted all open payments');
        return Command::SUCCESS;
    }
}
