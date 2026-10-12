<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Themes;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Themes\ResetsThemes;

#[Description('Make the built-in classic theme active again.')]
#[Signature('p:theme:reset')]
class ResetCommand extends Command
{
    public function handle(ResetsThemes $themes): int
    {
        $themes->reset();

        $this->components->info('The panel is back on the classic theme.');

        return self::SUCCESS;
    }
}
