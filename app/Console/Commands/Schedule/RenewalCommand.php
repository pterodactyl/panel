<?php

namespace Pterodactyl\Console\Commands\Schedule;

use Exception;
use Throwable;
use Pterodactyl\Models\Server;
use Illuminate\Console\Command;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ServerDeletionService;

class RenewalCommand extends Command
{
    protected SuspensionService $suspensionService;
    protected ServerDeletionService $deletionService;

    /**
     * @var string
     */
    protected $signature = 'p:schedule:renewal';

    /**
     * @var string
     */
    protected $description = 'Process renewals for servers.';

    /**
     * DeleteUserCommand constructor.
     */
    public function __construct(
        SuspensionService $suspensionService,
        ServerDeletionService $deletionService
    )
    {
        parent::__construct();

        $this->suspensionService = $suspensionService;
        $this->deletionService = $deletionService;
    }

    /**
     * Handle command execution.
     */
    public function handle(Server $server)
    {
        $this->line('Executing daily renewal script.');    
        $this->process($server);
        $this->line('Renewals completed successfully.');
    }

    /**
     * Takes one day off of the time a server has until it needs to be
     * renewed.
     */
    protected function process(Server $server)
    {
        $servers = $server->where('renewable', true)->get();
        $this->line('Processing renewals for '.$servers->count().' servers.');

        foreach ($servers as $svr) {
            $this->line('Renewing server '.$svr->name, false);

            $svr->update(['renewal' => $svr->renewal - 1]);

            if ($svr->renewal <= 0) {
                $this->line('Suspending server '.$svr->name, false);
                $this->suspensionService->toggle($svr, 'suspend');
            }

            if ($svr->renewal <= -7) {
                $this->line('Deleting server '.$svr->name, false);
                $this->deletionService->handle($svr);
            }
        };
    }
}