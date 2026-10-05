<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Environment\Addons;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

#[Description('Execute addon lifecycle hook scripts for the given event.')]
#[Signature('p:environment:addons:run-hooks
                            {event : The lifecycle event to run hooks for (e.g. post-install).}')]
class RunHooksCommand extends Command
{
    /**
     * Runs every executable "addons/<name>/hooks/<event>" script for the given lifecycle event when addon hooks are enabled.
     */
    public function handle(): int
    {
        if (! config('addons.hooks_enabled')) {
            return self::SUCCESS;
        }

        $event = $this->argument('event');
        if (! Str::isMatch('/^[a-z0-9-]+$/', $event)) {
            $this->components->error("Invalid hook event name: {$event}");

            return self::INVALID;
        }

        $hooks = Collection::make(glob(base_path("addons/*/hooks/{$event}")) ?: [])
            ->filter(fn (string $hook): bool => is_executable($hook))
            ->values();

        if ($hooks->isEmpty()) {
            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! $this->confirm(
            sprintf('Execute %d addon hook script(s) for the "%s" event? They run with the privileges of this process.', $hooks->count(), $event)
        )) {
            return self::SUCCESS;
        }

        $hooks->each($this->runHook(...));

        return self::SUCCESS;
    }

    /**
     * Streams a single hook's output, reporting a non-zero exit without aborting the remaining hooks.
     */
    private function runHook(string $hook): void
    {
        $this->components->info("Running addon hook: {$hook}");

        $result = Process::path(base_path())
            ->forever()
            ->run([$hook], fn (string $type, string $output) => $this->output->write($output));

        if ($result->failed()) {
            $this->components->warn("Addon hook exited with an error: {$hook}");
        }
    }
}
