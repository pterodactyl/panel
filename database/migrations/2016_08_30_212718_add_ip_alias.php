<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIpAlias extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->text('ip_alias')->nullable()->after('ip');
        });

        $allocations = DB::select('SELECT id, ip FROM allocations');
        foreach ($allocations as $allocation) {
            DB::update(
                'UPDATE allocations SET ip_alias = :ip WHERE id = :id',
                [
                    'ip' => $allocation->ip,
                    'id' => $allocation->id,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropColumn('ip_alias');
        });
    }
}
