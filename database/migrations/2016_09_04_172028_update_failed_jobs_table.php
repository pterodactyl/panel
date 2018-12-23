<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFailedJobsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->text('exception');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropColumn('exception');
        });
    }
}
