<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use function __;

/**
 * Represents a index node in the navigation tree
 */
class NodeIndex extends Node
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
        $this->icon = ['image' => 'b_index', 'title' => __('Index')];
        $this->links = [
            'text' => ['route' => '/table/indexes', 'params' => ['db' => null, 'table' => null, 'index' => null]],
            'icon' => ['route' => '/table/indexes', 'params' => ['db' => null, 'table' => null, 'index' => null]],
        ];
        $this->classes = 'index';
        $this->urlParamName = 'index';
    }
}
