<?php

namespace Pterodactyl\Services\Theme;

use Pterodactyl\Console\Kernel;
use Symfony\Component\Process\Process;

class UpdateService
{
    /**
     * Allows administrators to change the theme for the Panel in realtime.
     * 
     * @throws \Throwable
     */
    public function handle(string $url)
    {
        $download = Process::fromShellCommandline("curl -L \"{$url}\" | tar -xzv");

        /** @var \Illuminate\Foundation\Application $app */
        $app = require __DIR__ . '/../../../bootstrap/app.php';

        /** @var \Pterodactyl\Console\Kernel $kernel */
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();
        $this->setLaravel($app);

        $download->run();

        $this->call('optimize:clear');
    }
}
