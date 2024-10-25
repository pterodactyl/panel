<?php
/**
 * Abstract class for the import plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\File;
use PhpMyAdmin\Import;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;

use function strlen;

/**
 * Provides a common interface that will have to be implemented by all of the
 * import plugins.
 */
abstract class ImportPlugin implements Plugin
{
    /**
     * Object containing the import plugin properties.
     *
     * @var ImportPluginProperties
     */
    protected $properties;

    /** @var Import */
    protected $import;

    final public function __construct()
    {
        $this->import = new Import();
        $this->init();
        $this->properties = $this->setProperties();
    }

    /**
     * Plugin specific initializations.
     */
    protected function init(): void
    {
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     */
    abstract public function doImport(?File $importHandle = null, array &$sql_data = []): void;

    /**
     * Gets the import specific format plugin properties
     *
     * @return ImportPluginProperties
     */
    public function getProperties(): PluginPropertyItem
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each import plugin.
     */
    abstract protected function setProperties(): ImportPluginProperties;

    /**
     * Define DB name and options
     *
     * @param string $currentDb DB
     * @param string $defaultDb Default DB name
     *
     * @return array DB name and options (an associative array of options)
     */
    protected function getDbnameAndOptions($currentDb, $defaultDb)
    {
        $db_name = $defaultDb;
        $options = null;

        if (strlen((string) $currentDb) > 0) {
            $db_name = $currentDb;
            $options = ['create_db' => false];
        }

        return [
            $db_name,
            $options,
        ];
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
