<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use function __;

/**
 * Represents a view node in the navigation tree
 */
class NodeView extends NodeDatabaseChild
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
        $this->icon = ['image' => 'b_props', 'title' => __('View')];
        $this->links = [
            'text' => ['route' => '/sql', 'params' => ['pos' => 0, 'db' => null, 'table' => null]],
            'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
        ];
        $this->classes = 'view';
        $this->urlParamName = 'table';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType()
    {
        return 'view';
    }
}
