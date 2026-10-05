<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Extensions\RemovesExtensions;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

#[Description("Remove an extension's files, published assets, and install record.")]
#[Signature('p:extension:remove {id : The extension identifier.}')]
class RemoveCommand extends Command
{
    public function handle(RemovesExtensions $installer): int
    {
        $id = $this->argument('id');

        if ($this->input->isInteractive() && ! $this->confirm("Remove extension \"{$id}\"? Its files and published assets are deleted.")) {
            return self::SUCCESS;
        }

        try {
            $installer->remove($id);
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Extension "%s" removed. Database tables it created and its settings were left in place.',
            $id,
        ));

        return self::SUCCESS;
    }
}
