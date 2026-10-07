<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * The artisan commands and schedule callbacks extensions have registered this boot,
 * keyed by extension id. Populated by ExtensionProvider::registerCommands() and
 * registerSchedule() once a provider has booted successfully; consumed when the console
 * application is created and when the scheduler is resolved, so nothing reaches Artisan
 * for an extension that failed to boot or is not enabled.
 */
final class ExtensionConsoleRegistry
{
    /** @var array<string, list<class-string<Command>>> */
    private array $commands = [];

    /** @var array<string, list<Closure(Schedule): void>> */
    private array $schedules = [];

    public function __construct(
        private readonly Application $app,
        private readonly ExtensionRepository $extensions,
    ) {}

    /** @param list<string> $classes */
    public function registerCommands(string $identifier, array $classes): void
    {
        $commands = [];
        foreach ($classes as $class) {
            throw_unless(is_subclass_of($class, Command::class), InvalidArgumentException::class, sprintf('Extension "%s" registered "%s", which is not a console command.', $identifier, $class));
            $commands[] = $class;
        }

        $this->commands[$identifier] = array_values(array_unique([...$this->commands[$identifier] ?? [], ...$commands]));
    }

    /** @param Closure(Schedule): void $callback */
    public function registerSchedule(string $identifier, Closure $callback): void
    {
        $this->schedules[$identifier][] = $callback;
    }

    /**
     * Hand the commands of every available extension to the console application, once the
     * panel's own are registered (Pterodactyl\Console\Kernel). A command whose name or an
     * alias does not start with `<id>:`, or that would replace a command that already
     * exists, is refused and recorded against its extension; its other commands still load.
     */
    public function resolveCommands(Artisan $artisan): void
    {
        foreach ($this->commands as $identifier => $commands) {
            if (! $this->extensions->isAvailable($identifier)) {
                continue;
            }

            foreach ($commands as $class) {
                try {
                    $command = $this->app->make($class);
                    throw_unless($command instanceof Command, InvalidArgumentException::class, sprintf('Extension "%s" registered "%s", which is not a console command.', $identifier, $class));
                    foreach ([$command->getName(), ...array_map(JsonValueGuard::string(...), $command->getAliases())] as $name) {
                        throw_unless($name !== null && str_starts_with($name, $identifier.':'), InvalidArgumentException::class, sprintf('Extension "%s" cannot register the command "%s": its names must start with "%s:".', $identifier, $name, $identifier));
                        throw_if($artisan->has($name), InvalidArgumentException::class, sprintf('Extension "%s" cannot register the command "%s": a command with that name already exists.', $identifier, $name));
                    }

                    $artisan->addCommand($command);
                } catch (Throwable $throwable) {
                    $this->extensions->recordFailure($identifier, $throwable->getMessage(), $throwable, 'command');
                }
            }
        }
    }

    /**
     * Let every available extension define its scheduled tasks. Each task an extension
     * defines only runs while that extension is still enabled, and a failed run is
     * recorded against the extension. An extension whose callback throws schedules nothing.
     */
    public function schedule(Schedule $schedule): void
    {
        foreach ($this->schedules as $identifier => $callbacks) {
            if (! $this->extensions->isAvailable($identifier)) {
                continue;
            }

            foreach ($callbacks as $callback) {
                $defined = count($schedule->events());
                $failure = null;
                try {
                    $callback($schedule);
                } catch (Throwable $throwable) {
                    $failure = $throwable;
                    $this->extensions->recordFailure($identifier, $throwable->getMessage(), $throwable, 'schedule');
                }

                foreach (array_slice($schedule->events(), $defined) as $event) {
                    // Events cannot be removed from the schedule again, so a half-defined
                    // set is kept from ever running instead.
                    $event->skip($failure instanceof Throwable)
                        ->when(fn (): bool => $this->extensions->isAvailable($identifier))
                        ->onFailure(function () use ($identifier, $event): void {
                            $this->extensions->recordFailure($identifier, sprintf('Scheduled task "%s" failed.', $event->getSummaryForDisplay()), phase: 'schedule');
                        });
                }
            }
        }
    }

    /** @return array{commands: array<string, list<class-string<Command>>>, schedules: array<string, list<Closure(Schedule): void>>} */
    public function snapshot(): array
    {
        return ['commands' => $this->commands, 'schedules' => $this->schedules];
    }

    /** @param array{commands: array<string, list<class-string<Command>>>, schedules: array<string, list<Closure(Schedule): void>>} $snapshot */
    public function restore(array $snapshot): void
    {
        $this->commands = $snapshot['commands'];
        $this->schedules = $snapshot['schedules'];
    }
}
