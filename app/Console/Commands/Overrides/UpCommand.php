<?php

namespace Pterodactyl\Console\Commands\Overrides;

use Pterodactyl\Console\RequiresDatabaseMigrations;
use Illuminate\Foundation\Console\UpCommand as BaseUpCommand;

class UpCommand extends BaseUpCommand
{
    use RequiresDatabaseMigrations;

    /**
     * @return bool|int
     */
    public function handle()
    {
        if (! $this->hasCompletedMigrations()) {
            return $this->showMigrationWarning();
        }

        return parent::handle();
    }
}
