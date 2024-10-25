<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;

use function __;
use function _pgettext;

/**
 * Represents a container for index nodes in the navigation tree
 */
class NodeIndexContainer extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Indexes'), Node::CONTAINER);
        $this->icon = ['image' => 'b_index', 'title' => __('Indexes')];
        $this->links = [
            'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
        ];
        $this->realName = 'indexes';

        $newLabel = _pgettext('Create new index', 'New');
        $new = NodeFactory::getInstanceForNewNode($newLabel, 'new_index italics');
        $new->icon = ['image' => 'b_index_add', 'title' => $newLabel];
        $new->links = [
            'text' => [
                'route' => '/table/indexes',
                'params' => ['create_index' => 1, 'added_fields' => 2, 'db' => null, 'table' => null],
            ],
            'icon' => [
                'route' => '/table/indexes',
                'params' => ['create_index' => 1, 'added_fields' => 2, 'db' => null, 'table' => null],
            ],
        ];
        $this->addChild($new);
    }
}
