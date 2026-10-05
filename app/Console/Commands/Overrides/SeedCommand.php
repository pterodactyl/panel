<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Overrides;

use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;
use Pterodactyl\Console\RequiresDatabaseMigrations;

class SeedCommand extends BaseSeedCommand
{
    use RequiresDatabaseMigrations;

    /**
     * Block someone from running this seed command if they have not completed
     * the migration process.
     */
    public function handle(): int
    {
        if (! $this->hasCompletedMigrations()) {
            $this->showMigrationWarning();

            return 1;
        }

        return parent::handle();
    }
}
