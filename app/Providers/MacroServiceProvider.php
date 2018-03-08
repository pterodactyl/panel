<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        File::macro('humanReadableSize', function ($path, $precision = 2) {
            $size = File::size($path);
            static $units = ['B', 'kB', 'MB', 'GB', 'TB'];

            $i = 0;
            while (($size / 1024) > 0.9) {
                $size = $size / 1024;
                $i++;
            }

            return round($size, ($i < 2) ? 0 : $precision) . ' ' . $units[$i];
        });
    }
}
