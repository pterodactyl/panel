<?php
/**
 * Super class of CSV import plugins for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;

use function __;

/**
 * Super class of the import plugins for the CSV format
 */
abstract class AbstractImportCsv extends ImportPlugin
{
    final protected function getGeneralOptions(): OptionsPropertyMainGroup
    {
        $generalOptions = new OptionsPropertyMainGroup('general_opts');

        // create common items and add them to the group
        $leaf = new BoolPropertyItem(
            'replace',
            __(
                'Update data when duplicate keys found on import (add ON DUPLICATE KEY UPDATE)'
            )
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'terminated',
            __('Columns separated with:')
        );
        $leaf->setSize(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'enclosed',
            __('Columns enclosed with:')
        );
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'escaped',
            __('Columns escaped with:')
        );
        $leaf->setSize(2);
        $leaf->setLen(2);
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'new_line',
            __('Lines terminated with:')
        );
        $leaf->setSize(2);
        $generalOptions->addProperty($leaf);

        return $generalOptions;
    }
}
