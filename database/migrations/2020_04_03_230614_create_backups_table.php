<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBackupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $db = config('database.default');
        // There exists a backups plugin for the 0.7 version of the Panel. However, it didn't properly
        // namespace itself so now we have to deal with these tables being in the way of tables we're trying
        // to use. For now, just rename them to maintain the data.
        $results = DB::select('SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ? AND table_name NOT LIKE \'%_plugin_bak\'', [
            config("database.connections.{$db}.database"),
            'backup%',
        ]);

        // Take any of the results, most likely "backups" and "backup_logs" and rename them to have a
        // suffix so data isn't completely lost, but they're no longer in the way of this migration...
        foreach ($results as $result) {
            Schema::rename($result->TABLE_NAME, $result->TABLE_NAME . '_plugin_bak');
        }

        Schema::create('backups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('server_id');
            $table->char('uuid', 36);
            $table->string('name');
            $table->text('ignored_files');
            $table->string('disk');
            $table->string('sha256_hash')->nullable();
            $table->integer('bytes')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('uuid');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('backups');
    }
}
