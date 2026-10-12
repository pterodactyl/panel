<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Themes\ThemeRepository;

#[Description('List the built-in and installed themes and which one is active.')]
#[Signature('p:theme:list')]
class ListCommand extends Command
{
    public function handle(ThemeRepository $themes): int
    {
        $active = $themes->active();
        $rows = [[ThemeRepository::CLASSIC, 'Classic', '', $active === ThemeRepository::CLASSIC ? 'active' : 'built in']];
        foreach ($themes->installed() as $theme) {
            $state = $theme->id === $active ? 'active' : 'available';
            $problem = $themes->activationProblem($theme->id);
            $rows[] = [$theme->id, $theme->name, $theme->theme?->extends, $problem === null ? $state : "{$state}, unusable: {$problem}"];
        }

        $this->table(['ID', 'Name', 'Extends', 'State'], $rows);

        return self::SUCCESS;
    }
}
