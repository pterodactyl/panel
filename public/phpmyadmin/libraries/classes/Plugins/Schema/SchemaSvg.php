<?php
/**
 * PDF schema export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Svg\SvgRelationSchema;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Plugins\SchemaPluginProperties;

use function __;

/**
 * Handles the schema export for the SVG format
 */
class SchemaSvg extends SchemaPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'svg';
    }

    /**
     * Sets the schema export SVG properties
     */
    protected function setProperties(): SchemaPluginProperties
    {
        $schemaPluginProperties = new SchemaPluginProperties();
        $schemaPluginProperties->setText('SVG');
        $schemaPluginProperties->setExtension('svg');
        $schemaPluginProperties->setMimeType('application/svg');

        // create the root group that will be the options field for
        // $schemaPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // specific options main group
        $specificOptions = new OptionsPropertyMainGroup('general_opts');
        // add options common to all plugins
        $this->addCommonOptions($specificOptions);

        // create leaf items and add them to the group
        $leaf = new BoolPropertyItem(
            'all_tables_same_width',
            __('Same width for all tables')
        );
        $specificOptions->addProperty($leaf);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($specificOptions);

        // set the options for the schema export plugin property item
        $schemaPluginProperties->setOptions($exportSpecificOptions);

        return $schemaPluginProperties;
    }

    /**
     * Exports the schema into SVG format.
     *
     * @param string $db database name
     */
    public function exportSchema($db): bool
    {
        $export = new SvgRelationSchema($db);
        $export->showOutput();

        return true;
    }
}
