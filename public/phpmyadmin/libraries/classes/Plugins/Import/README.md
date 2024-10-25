# Import plugin creation

This directory holds import plugins for phpMyAdmin. Any new plugin should
basically follow the structure presented here. The messages must use our
gettext mechanism, see https://wiki.phpmyadmin.net/pma/Gettext_for_developers.

```php
<?php
/**
 * [Name] import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Plugins\ImportPlugin;
use function strlen;

/**
 * Handles the import for the [Name] format
 */
class Import[Name] extends ImportPlugin
{
    /**
     * optional - declare variables and descriptions
     *
     * @var type
     */
    private $myOptionalVariable;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setProperties();
    }

    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return void
     */
    protected function setProperties()
    {
        $importPluginProperties = new PhpMyAdmin\Properties\Plugins\ImportPluginProperties();
        $importPluginProperties->setText('[name]');             // the name of your plug-in
        $importPluginProperties->setExtension('[ext]');         // extension this plug-in can handle
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup(
            'Format Specific Options'
        );

        // general options main group
        $generalOptions = new PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup(
            'general_opts'
        );

        // optional :
        // create primary items and add them to the group
        // type - one of the classes listed in libraries/properties/options/items/
        // name - form element name
        // text - description in GUI
        // size - size of text element
        // len  - maximal size of input
        // values - possible values of the item
        $leaf = new PhpMyAdmin\Properties\Options\Items\RadioPropertyItem(
            'structure_or_data'
        );
        $leaf->setValues(
            [
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data'),
            ]
        );
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);
        $this->properties = $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array &$sql_data 2-element array with sql data
     *
     * @return void
     */
    public function doImport(&$sql_data = [])
    {
        // get globals (others are optional)
        global $error, $timeout_passed, $finished;

        $buffer = '';
        while (! ($finished && $i >= $len) && ! $error && ! $timeout_passed) {
            $data = $this->import->getNextChunk();
            if ($data === false) {
                // subtract data we didn't handle yet and stop processing
                $GLOBALS['offset'] -= strlen($buffer);
                break;
            }

            if ($data === true) {
                // Handle rest of buffer
            } else {
                // Append new data to buffer
                $buffer .= $data;
            }
            // PARSE $buffer here, post sql queries using:
            $this->import->runQuery($sql, $verbose_sql_with_comments, $sql_data);
        } // End of import loop
        // Commit any possible data in buffers
        $this->import->runQuery('', '', $sql_data);
    }

    /* optional:                                                     */
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Getter description
     *
     * @return type
     */
    private function getMyOptionalVariable(): type
    {
        return $this->myOptionalVariable;
    }

    /**
     * Setter description
     *
     * @param type $myOptionalVariable description
     *
     * @return void
     */
    private function _setMyOptionalVariable(type $myOptionalVariable): void
    {
        $this->myOptionalVariable = $myOptionalVariable;
    }
}
```
