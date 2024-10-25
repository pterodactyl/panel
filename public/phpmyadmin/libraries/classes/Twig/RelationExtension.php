<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\ConfigStorage\Relation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RelationExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        global $dbi;

        $relation = new Relation($dbi);

        return [
            new TwigFunction(
                'foreign_dropdown',
                [$relation, 'foreignDropdown'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_display_field',
                [$relation, 'getDisplayField'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_foreign_data',
                [$relation, 'getForeignData']
            ),
            new TwigFunction(
                'get_tables',
                [$relation, 'getTables']
            ),
            new TwigFunction(
                'search_column_in_foreigners',
                [$relation, 'searchColumnInForeigners']
            ),
        ];
    }
}
