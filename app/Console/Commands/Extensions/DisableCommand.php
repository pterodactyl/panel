<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

#[Description('Disable an extension without removing its files or data.')]
#[Signature('p:extension:disable {id : The extension identifier.}')]
class DisableCommand extends Command
{
    public function handle(SetsExtensionEnabled $installer): int
    {
        try {
            $installer->setEnabled($this->argument('id'), false);
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Extension "%s" disabled.', $this->argument('id')));

        return self::SUCCESS;
    }
}
