<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Themes\ThemeService;

#[Description('List installed themes and whether one is applied.')]
#[Signature('p:theme:list')]
class ListCommand extends Command
{
    public function handle(ThemeService $themes): int
    {
        $rows = array_map(
            fn (array $theme): array => [$theme['id'], $theme['name'], $theme['version']],
            array_values($themes->discovered()),
        );

        if ($rows === []) {
            $this->components->info('No themes installed. Drop one into '.$themes->directory().'.');
        } else {
            $this->table(['ID', 'Name', 'Version'], $rows);
        }

        $this->components->info(
            is_file($themes->publishedStylesheet())
                ? 'A theme is currently applied (public/assets/theme.css).'
                : 'No theme applied - the panel uses its stock tokens.',
        );

        return self::SUCCESS;
    }
}
