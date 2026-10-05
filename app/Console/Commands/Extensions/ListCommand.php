<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Extensions\ExtensionManager;
use Pterodactyl\Services\Extensions\ExtensionManifest;

#[Description('List every installed extension and its state.')]
#[Signature('p:extension:list')]
class ListCommand extends Command
{
    public function handle(ExtensionManager $manager): int
    {
        $records = $manager->records();

        $rows = $manager->discovered()->map(function (ExtensionManifest $manifest) use ($records): array {
            $record = $records->get($manifest->id);

            $state = match (true) {
                $record === null => 'not registered',
                $record->enabled => 'enabled',
                default => 'disabled',
            };

            return [
                $manifest->id,
                $manifest->name,
                $manifest->version,
                $manifest->hasUi() ? 'yes' : 'no',
                $state,
                $record->error ?? '',
            ];
        })->values()->all();

        foreach ($manager->discoveryErrors() as $directory => $error) {
            $rows[] = [$directory, '(invalid manifest)', '', '', 'error', $error];
        }

        if (empty($rows)) {
            $this->components->info('No extensions installed. Install one with p:extension:install.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Name', 'Version', 'UI', 'State', 'Error'], $rows);

        return self::SUCCESS;
    }
}
