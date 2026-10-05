<?php

declare(strict_types=1);

namespace Pterodactyl\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;

/**
 * @mixin Command
 */
trait RequiresDatabaseMigrations
{
    /**
     * Checks if the migrations have finished running by comparing the last migration file.
     */
    protected function hasCompletedMigrations(): bool
    {
        $migrator = $this->getLaravel()->make(Migrator::class);

        $files = $migrator->getMigrationFiles(database_path('migrations'));

        if (! $migrator->repositoryExists()) {
            return false;
        }

        return ! array_diff(array_keys($files), $migrator->getRepository()->getRan());
    }

    /**
     * Throw a massive error into the console to hopefully catch the users attention and get
     * them to properly run the migrations rather than ignoring all of the other previous
     * errors...
     */
    protected function showMigrationWarning(): void
    {
        $this->getOutput()->writeln('<options=bold>
| @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ |
|                                                                              |
|               Your database has not been properly migrated!                  |
|                                                                              |
| @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ |</>

You must run the following command to finish migrating your database:

  <fg=green;options=bold>php artisan migrate --step --force</>

You will not be able to use Pterodactyl Panel as expected without fixing your
database state by running the command above.
');

        $this->getOutput()->error('You must correct the error above before continuing.');
    }
}
