<?php

use Illuminate\Database\Migrations\Migration;

class MigrateToNewServiceSystem extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $service = DB::table('services')->where('author', config('pterodactyl.service.core'))->where('folder', 'srcds')->first();
            if (!$service) {
                return;
            }

            $options = DB::table('service_options')->where('service_id', $service->id)->get();
            $options->each(function ($item) {
                if ($item->tag === 'srcds' && $item->name === 'Insurgency') {
                    $item->tag = 'insurgency';
                } elseif ($item->tag === 'srcds' && $item->name === 'Team Fortress 2') {
                    $item->tag = 'tf2';
                } elseif ($item->tag === 'srcds' && $item->name === 'Custom Source Engine Game') {
                    $item->tag = 'source';
                }
                $item->save();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not doing reversals right now...
    }
}
