<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Core;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CoreExtension extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'link',
                [Core::class, 'linkURL']
            ),
        ];
    }
}
