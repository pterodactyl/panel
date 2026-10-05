<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

#[Description('Enable an installed extension, publishing its assets and running its migrations.')]
#[Signature('p:extension:enable {id : The extension identifier.}')]
class EnableCommand extends Command
{
    public function handle(SetsExtensionEnabled $installer): int
    {
        try {
            $installer->setEnabled($this->argument('id'), true);
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Extension "%s" enabled.', $this->argument('id')));

        return self::SUCCESS;
    }
}
