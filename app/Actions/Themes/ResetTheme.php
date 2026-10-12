<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Themes;

use Pterodactyl\Contracts\Themes\ResetsThemes;
use Pterodactyl\Services\Themes\ThemeRepository;

final readonly class ResetTheme implements ResetsThemes
{
    public function __construct(private ThemeRepository $themes) {}

    public function reset(): void
    {
        $this->themes->setActive(ThemeRepository::CLASSIC);
    }
}
