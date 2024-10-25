<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Sanitize;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SanitizeExtension extends AbstractExtension
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
                'escape_js_string',
                [Sanitize::class, 'escapeJsString'],
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'js_format',
                [Sanitize::class, 'jsFormat'],
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'sanitize',
                [Sanitize::class, 'sanitizeMessage'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'get_js_value',
                [Sanitize::class, 'getJsValue'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
