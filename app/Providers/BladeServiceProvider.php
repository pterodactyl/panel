<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->app->make(BladeCompiler::class)
            ->directive('datetimeHuman', fn (string $expression): string => "<?php echo \Carbon\CarbonImmutable::createFromFormat(\Carbon\CarbonImmutable::DEFAULT_TO_STRING_FORMAT, $expression)->setTimezone(config('app.timezone'))->toDateTimeString(); ?>");
    }
}
