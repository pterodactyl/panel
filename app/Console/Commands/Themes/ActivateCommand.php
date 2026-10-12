<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Themes\ActivatesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

#[Description('Make an installed, enabled theme the active theme.')]
#[Signature('p:theme:activate {id : The theme identifier, or "classic".}')]
class ActivateCommand extends Command
{
    public function handle(ActivatesThemes $themes): int
    {
        try {
            $themes->activate($this->argument('id'));
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Theme "%s" is now active.', $this->argument('id')));

        return self::SUCCESS;
    }
}
