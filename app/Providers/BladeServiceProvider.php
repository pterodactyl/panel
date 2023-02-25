<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->app->make('blade.compiler')
            ->directive('datetimeHuman', function ($expression) {
                return "<?php echo \Carbon\CarbonImmutable::createFromFormat(\Carbon\CarbonImmutable::DEFAULT_TO_STRING_FORMAT, $expression)->setTimezone(config('app.timezone'))->toDateTimeString(); ?>";
            });
    }
}
