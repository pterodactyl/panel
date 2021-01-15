<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDatabaseHostNodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_host_node', function (Blueprint $table) {
            $table->integer('node_id')->unique();
            $table->integer('database_host_id');

            $table->unique(['node_id', 'database_host_id']);
        });

        DB::transaction(function () {
            foreach (DB::select('SELECT id, node_id FROM database_hosts') as $datum) {
                if (! is_null($datum->node_id)) {
                    DB::insert('INSERT INTO database_host_node (node_id, database_host_id) VALUES (?, ?)', [
                        $datum->node_id,
                        $datum->id
                    ]);
                }
            }
        });

        Schema::table('database_hosts', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('database_hosts', function (Blueprint $table) {
            $table->integer('node_id')->unsigned()->nullable()->after('max_databases');

            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('set null');
        });

        Schema::dropIfExists('database_host_node');
    }
}
