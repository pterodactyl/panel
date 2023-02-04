<?php

use Pterodactyl\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    private array $keys = [
        ['mail:host', 'mail:mailers:smtp:host'],
        ['mail:port', 'mail:mailers:smtp:port'],
        ['mail:encryption', 'mail:mailers:smtp:encryption'],
        ['mail:username', 'mail:mailers:smtp:username'],
        ['mail:password', 'mail:mailers:smtp:password'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = Setting::all();

        // Gets the first column in our key table and gets all matching settings.
        $oldKeys = array_column($this->keys, 0);
        $oldSettings = $settings->filter(fn (Setting $setting) => in_array($setting->key, $oldKeys));

        // Gets the second column in our key table and gets all matching settings.
        $newKeys = array_column($this->keys, 1);
        $newSettings = $settings->filter(fn (Setting $setting) => in_array($setting->key, $newKeys));

        // Map all the old settings to their new key.
        $oldSettings->map(function (Setting $setting) use ($oldKeys) {
            $row = array_search($setting->key, $oldKeys, true);
            $setting->key = $this->keys[$row][1];

            return $setting;
            // Check if any settings with the new key already exist.
        })->filter(function (Setting $setting) use ($newSettings) {
            if ($newSettings->contains('key', $setting->key)) {
                return false;
            }

            return true;
            // Update the settings to use their new keys if they don't already exist.
        })->each(fn (Setting $setting) => $setting->save());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $settings = Setting::all();

            // Gets the second column in our key table and gets all matching settings.
            $newKeys = array_column($this->keys, 0);
            $newSettings = $settings->filter(fn (Setting $setting) => in_array($setting->key, $newKeys));

            // Delete all settings that already have the new key.
            $newSettings->each(fn (Setting $setting) => $setting->delete());

            // Gets the first column in our key table and gets all matching settings.
            $oldKeys = array_column($this->keys, 1);
            $oldSettings = $settings->filter(fn (Setting $setting) => in_array($setting->key, $oldKeys));

            // Map all the old settings to their new key.
            $oldSettings->map(function (Setting $setting) use ($oldKeys) {
                $row = array_search($setting->key, $oldKeys, true);
                $setting->key = $this->keys[$row][0];

                return $setting;
                // Update the settings to use their new keys if they don't already exist.
            })->each(fn (Setting $setting) => $setting->save());
        });
    }
};
