<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Overrides;

use Illuminate\Foundation\Console\UpCommand as BaseUpCommand;
use Pterodactyl\Console\RequiresDatabaseMigrations;

class UpCommand extends BaseUpCommand
{
    use RequiresDatabaseMigrations;

    /**
     * Block someone from running this up command if they have not completed
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
