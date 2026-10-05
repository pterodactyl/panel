<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Themes\ResetsThemes;

#[Description('Remove the applied theme, restoring the stock panel look.')]
#[Signature('p:theme:reset')]
class ResetCommand extends Command
{
    public function handle(ResetsThemes $themes): int
    {
        $themes->reset();

        $this->components->info('Theme removed - the panel is back on its stock tokens.');

        return self::SUCCESS;
    }
}
