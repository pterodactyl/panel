<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UrlExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'get_hidden_inputs',
                [Url::class, 'getHiddenInputs'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_hidden_fields',
                [Url::class, 'getHiddenFields'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_common',
                [Url::class, 'getCommon'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_common_raw',
                [Url::class, 'getCommonRaw'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'url',
                [Url::class, 'getFromRoute'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
