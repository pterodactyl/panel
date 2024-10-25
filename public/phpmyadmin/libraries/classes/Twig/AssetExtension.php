<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Theme;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('image', [$this, 'getImagePath']),
        ];
    }

    public function getImagePath(?string $filename = null, ?string $fallback = null): string
    {
        global $theme;

        if (! $theme instanceof Theme) {
            return '';
        }

        return $theme->getImgPath($filename, $fallback);
    }
}
