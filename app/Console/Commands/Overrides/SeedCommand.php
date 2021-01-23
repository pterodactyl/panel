<?php

namespace Pterodactyl\Console\Commands\Overrides;

use Pterodactyl\Console\RequiresDatabaseMigrations;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;

class SeedCommand extends BaseSeedCommand
{
    use RequiresDatabaseMigrations;

    /**
     * Block someone from running this seed command if they have not completed the migration
     * process.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->hasCompletedMigrations()) {
            return $this->showMigrationWarning();
        }

        return parent::handle();
    }
}
