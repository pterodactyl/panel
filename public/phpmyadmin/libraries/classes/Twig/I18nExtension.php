<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Twig\Extensions\I18nExtension as TwigI18nExtension;
use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use Twig\TwigFilter;

class I18nExtension extends TwigI18nExtension
{
    public function __construct()
    {
        TransNode::$notesLabel = '// l10n: ';
        TransNode::$enableMoTranslator = true;
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            // This is just a performance override
            new TwigFilter('trans', '_gettext'),
        ];
    }
}
