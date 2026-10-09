<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;

#[Description('List every installed extension and its state.')]
#[Signature('p:extension:list')]
class ListCommand extends Command
{
    public function handle(ExtensionRepository $extensions): int
    {
        $records = $extensions->records();

        $rows = $extensions->discovered()->map(function (ExtensionManifest $manifest) use ($records): array {
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

        foreach ($extensions->discoveryErrors() as $directory => $error) {
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
