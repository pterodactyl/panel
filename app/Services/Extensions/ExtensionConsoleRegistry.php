<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Scheduling\Schedule;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * The artisan commands and schedule callbacks extensions have registered this boot,
 * keyed by extension id. Populated by ExtensionProvider::registerCommands() and
 * registerSchedule() once a provider has booted successfully; consumed when the console
 * application starts and when the scheduler is resolved, so nothing reaches Artisan for
 * an extension that failed to boot or is not enabled.
 */
final class ExtensionConsoleRegistry
{
    /** @var array<string, list<class-string<Command>>> */
    private array $commands = [];

    /** @var array<string, list<Closure(Schedule): void>> */
    private array $schedules = [];

    public function __construct(private readonly ExtensionRepository $extensions) {}

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

    /** Hand the commands of every available extension to the starting console application. */
    public function resolveCommands(Artisan $artisan): void
    {
        foreach ($this->commands as $identifier => $commands) {
            if (! $this->extensions->isAvailable($identifier)) {
                continue;
            }

            try {
                $artisan->resolveCommands($commands);
            } catch (Throwable $throwable) {
                $this->extensions->recordFailure($identifier, $throwable->getMessage(), $throwable, 'command');
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
