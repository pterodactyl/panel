<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Themes;

use Pterodactyl\Contracts\Themes\ActivatesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Themes\ThemeRepository;

final readonly class ActivateTheme implements ActivatesThemes
{
    public function __construct(private ThemeRepository $themes) {}

    public function activate(string $id): void
    {
        $problem = $this->themes->activationProblem($id);
        throw_if($problem !== null, InvalidExtensionException::class, $problem);

        $this->themes->setActive($id);
    }
}
