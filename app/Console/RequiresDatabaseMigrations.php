<?php

namespace Pterodactyl\Console;

/**
 * @mixin \Illuminate\Console\Command
 */
trait RequiresDatabaseMigrations
{
    /**
     * Checks if the migrations have finished running by comparing the last migration file.
     */
    protected function hasCompletedMigrations(): bool
    {
        /** @var \Illuminate\Database\Migrations\Migrator $migrator */
        $migrator = $this->getLaravel()->make('migrator');

        $files = $migrator->getMigrationFiles(database_path('migrations'));

        if (!$migrator->repositoryExists()) {
            return false;
        }

        if (array_diff(array_keys($files), $migrator->getRepository()->getRan())) {
            return false;
        }

        return true;
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
