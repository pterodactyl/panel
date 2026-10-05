<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveDefaultNullValueOnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('external_id')->default(null)->change();
        });

        DB::transaction(function (): void {
            DB::table('users')->where('external_id', '=', 'NULL')->update([
                'external_id' => null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This should not be rolled back.
    }
}
