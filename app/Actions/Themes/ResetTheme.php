<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Themes;

use Pterodactyl\Contracts\Themes\ResetsThemes;
use Pterodactyl\Services\FilesystemChanges;
use Pterodactyl\Services\Themes\ThemeService;

final readonly class ResetTheme implements ResetsThemes
{
    public function __construct(private ThemeService $themes, private FilesystemChanges $files) {}

    /** Remove the published theme, restoring the stock look. */
    public function reset(): void
    {
        $this->files->run([
            $this->themes->publishedStylesheet() => null,
            $this->themes->publishedAssetsDirectory() => null,
        ], static function (): void {});
    }
}
