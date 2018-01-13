<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->app->make('blade.compiler')
            ->directive('datetimeHuman', function ($expression) {
                return "<?php echo \Cake\Chronos\Chronos::createFromFormat(\Cake\Chronos\Chronos::DEFAULT_TO_STRING_FORMAT, $expression)->setTimezone(config('app.timezone'))->toDateTimeString(); ?>";
            });
    }
}
