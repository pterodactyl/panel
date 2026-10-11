<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Location;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Locations\CreatesLocations;

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
        $input = Fluent::make([
            'short' => $this->option('short') ?? $this->ask(trans('command/messages.location.ask_short')),
            'long' => $this->option('long') ?? $this->ask(trans('command/messages.location.ask_long')),
        ]);

        try {
            $location = $locations->create([
                'short' => $input->get('short') === null ? null : $input->string('short')->toString(),
                'long' => $input->get('long') === null ? null : $input->string('long')->toString(),
            ]);
        } catch (ValidationException $validationException) {
            foreach ($validationException->validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $this->line(trans('command/messages.location.created', [
            'name' => $location->short,
            'id' => $location->id,
        ]));

        return 0;
    }
}
