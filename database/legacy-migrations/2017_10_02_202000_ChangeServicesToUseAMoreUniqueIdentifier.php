<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

class ChangeServicesToUseAMoreUniqueIdentifier extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->dropUnique(['file']);

            $table->string('author')->change();
            $table->char('uuid', 36)->after('id');
            $table->dropColumn('folder');
            $table->dropColumn('startup');
            $table->dropColumn('index_file');
        });

        DB::table('services')->get(['id', 'author', 'uuid'])->each(function ($service): void {
            DB::table('services')->where('id', $service->id)->update([
                'author' => ($service->author === 'ptrdctyl-v040-11e6-8b77-86f30ca893d3') ? 'support@pterodactyl.io' : 'unknown@unknown-author.com',
                'uuid' => Uuid::uuid4()->toString(),
            ]);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn('uuid');
            $table->string('folder')->nullable();
            $table->text('startup')->nullable();
            $table->text('index_file');
            $table->string('author', 36)->change();

            $table->unique('name');
            $table->unique('folder', 'services_file_unique');
        });
    }
}
