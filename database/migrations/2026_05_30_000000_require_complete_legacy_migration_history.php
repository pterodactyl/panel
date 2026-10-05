<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Refuse to upgrade a database that is missing any 1.x migration. The 1.x
     * migrations live in migrations/old and never run here, so a database from an
     * older 1.x release would otherwise be upgraded without part of its schema.
     * Fresh installs record every one of them through the schema dump and pass.
     * Runs before any other 2.x migration and writes nothing.
     */
    public function up(): void
    {
        $expected = collect(File::files(database_path('migrations/old')))
            ->map(fn (SplFileInfo $file): string => $file->getBasename('.php'));

        $recorded = DB::table('migrations')->whereIn('migration', $expected->all())->pluck('migration');
        $missing = $expected->diff($recorded)->sort()->values();

        throw_if($missing->isNotEmpty(), RuntimeException::class, sprintf(
            'This database is missing %d Pterodactyl Panel 1.x migration(s), starting with %s. '
            .'Update the Panel to the latest 1.x release and run "php artisan migrate --force" there before upgrading to 2.x. '
            .'No changes were made.',
            $missing->count(),
            $missing->first(),
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse: this migration only reads.
    }
};
