<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Locations\CreatesLocations;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\LocationRules;

#[Description('Creates a new location on the system via the CLI.')]
#[Signature('p:location:make
                            {--short= : The shortcode name of this location (ex. us1).}
                            {--long= : A longer description of this location.}')]
class MakeLocationCommand extends Command
{
    /**
     * Handle the command execution process.
     */
    public function handle(CreatesLocations $locations): int
    {
        $short = $this->option('short') ?? $this->ask(trans('command/messages.location.ask_short'));
        $long = $this->option('long') ?? $this->ask(trans('command/messages.location.ask_long'));

        $validator = Validator::make(['short' => $short, 'long' => $long], LocationRules::rules());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $location = $locations->create([
            'short' => JsonValueGuard::string($short),
            'long' => JsonValueGuard::nullableString($long),
        ]);
        $this->line(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]));

        return 0;
    }
}
