<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGenericServerStatusColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('status')->nullable()->after('description');
        });

        DB::table('servers')->where('suspended', 1)->update(['status' => 'suspended']);
        DB::table('servers')->where('installed', 1)->update(['status' => 'installing']);
        DB::table('servers')->where('installed', 1)->update(['status' => 'install_failed']);

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('suspended');
            $table->dropColumn('installed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->unsignedTinyInteger('suspended')->default(0);
            $table->unsignedTinyInteger('installed')->default(0);
        });

        DB::table('servers')->where('status', 'suspended')->update(['suspended' => 1]);
        DB::table('servers')->whereNull('status')->update(['installed' => 1]);
        DB::table('servers')->where('status', 'install_failed')->update(['installed' => 2]);

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
