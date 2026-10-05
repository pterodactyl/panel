<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Themes\AppliesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

#[Description('Apply a theme by publishing its token overrides.')]
#[Signature('p:theme:apply {id : The theme identifier.}')]
class ApplyCommand extends Command
{
    public function handle(AppliesThemes $themes): int
    {
        try {
            $themes->apply($this->argument('id'));
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Theme "%s" applied. Reload the panel to see it.', $this->argument('id')));

        return self::SUCCESS;
    }
}
