<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TransferOldTasksToNewScheduler extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $tasks = DB::table('tasks_old')->get();

            $tasks->each(function ($task) {
                $schedule = DB::table('schedules')->insertGetId([
                    'server_id' => $task->server_id,
                    'name' => null,
                    'cron_day_of_week' => $task->day_of_week,
                    'cron_day_of_month' => $task->day_of_month,
                    'cron_hour' => $task->hour,
                    'cron_minute' => $task->minute,
                    'is_active' => (bool) $task->active,
                    'is_processing' => false,
                    'last_run_at' => $task->last_run,
                    'next_run_at' => $task->next_run,
                    'created_at' => $task->created_at,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]);

                DB::table('tasks')->insert([
                    'schedule_id' => $schedule,
                    'sequence_id' => 1,
                    'action' => $task->action,
                    'payload' => $task->data,
                    'time_offset' => 0,
                    'is_queued' => false,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'created_at' => Carbon::now()->toDateTimeString(),
                ]);

                DB::table('tasks_old')->delete($task->id);
            });
        });

        Schema::dropIfExists('tasks_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tasks_old', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('server_id');
            $table->tinyInteger('active')->default(1);
            $table->string('action');
            $table->text('data');
            $table->unsignedTinyInteger('queued')->default(0);
            $table->string('year')->default('*');
            $table->string('month')->default('*');
            $table->string('day_of_week')->default('*');
            $table->string('day_of_month')->default('*');
            $table->string('minute')->default('*');
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run');
            $table->timestamps();
        });
    }
}
