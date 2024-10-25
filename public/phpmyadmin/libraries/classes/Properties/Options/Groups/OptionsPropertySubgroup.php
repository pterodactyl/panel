<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Properties\PropertyItem;

/**
 * Group property item class of type subgroup
 */
class OptionsPropertySubgroup extends OptionsPropertyGroup
{
    /**
     * Subgroup Header
     *
     * @var PropertyItem|null
     */
    private $subgroupHeader;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns the property item type of either an instance of
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool",
     *  "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main"
     *  or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     *
     * @return string
     */
    public function getItemType()
    {
        return 'subgroup';
    }

    /**
     * Gets the subgroup header
     *
     * @return PropertyItem|null
     */
    public function getSubgroupHeader()
    {
        return $this->subgroupHeader;
    }

    /**
     * Sets the subgroup header
     *
     * @param PropertyItem $subgroupHeader subgroup header
     */
    public function setSubgroupHeader($subgroupHeader): void
    {
        $this->subgroupHeader = $subgroupHeader;
    }
}
