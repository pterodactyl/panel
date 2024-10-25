<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use function __;

/**
 * Represents a event node in the navigation tree
 */
class NodeEvent extends NodeDatabaseChild
{
    /**
     * Initialises the class
     *
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $isGroup = false)
    {
        parent::__construct($name, $type, $isGroup);
        $this->icon = ['image' => 'b_events', 'title' => __('Event')];
        $this->links = [
            'text' => [
                'route' => '/database/events',
                'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
            ],
            'icon' => [
                'route' => '/database/events',
                'params' => ['export_item' => 1, 'db' => null, 'item_name' => null],
            ],
        ];
        $this->classes = 'event';
        $this->urlParamName = 'item_name';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'event';
    }
}
