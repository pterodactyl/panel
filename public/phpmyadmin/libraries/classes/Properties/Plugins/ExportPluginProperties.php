<?php
/**
 * Properties class for the export plug-in
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Plugins;

/**
 * Defines possible options and getters and setters for them.
 *
 * @todo    modify descriptions if needed, when the plug-in properties are integrated
 */
class ExportPluginProperties extends PluginPropertyItem
{
    /**
     * Whether each plugin has to be saved as a file
     *
     * @var bool
     */
    private $forceFile = false;

    /**
     * Returns the property item type of either an instance of
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool", "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main" or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return 'export';
    }

    /**
     * Gets the force file parameter
     */
    public function getForceFile(): bool
    {
        return $this->forceFile;
    }

    /**
     * Sets the force file parameter
     */
    public function setForceFile(bool $forceFile): void
    {
        $this->forceFile = $forceFile;
    }
}
