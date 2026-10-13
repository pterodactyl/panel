<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Themes\ThemeRepository;

#[Description('Make the built-in classic theme active again.')]
#[Signature('p:theme:reset')]
class ResetCommand extends Command
{
    public function handle(ThemeRepository $themes): int
    {
        $themes->setActive(ThemeRepository::CLASSIC);

        $this->components->info('The panel is back on the classic theme.');

        return self::SUCCESS;
    }
}
