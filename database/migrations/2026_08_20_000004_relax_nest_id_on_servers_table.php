<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nests are gone from the application but their data is deliberately kept, so
     * servers.nest_id survives with every value it already held. Servers no longer
     * write the column, so it is released from its foreign key and relaxed to
     * nullable — new rows simply leave it empty.
     *
     * The column check guards panels that ran the earlier revision of this
     * migration, which dropped the column outright before that was reversed.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('servers', 'nest_id')) {
            return;
        }

        Schema::table('servers', function (Blueprint $table): void {
            $table->dropForeign(['nest_id']);
        });

        Schema::table('servers', function (Blueprint $table): void {
            $table->unsignedInteger('nest_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('servers', 'nest_id')) {
            return;
        }

        Schema::table('servers', function (Blueprint $table): void {
            $table->unsignedInteger('nest_id')->nullable(false)->change();
            $table->foreign('nest_id')->references('id')->on('nests');
        });
    }
};
