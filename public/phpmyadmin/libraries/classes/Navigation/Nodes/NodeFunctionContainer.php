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
 * Represents a container for functions nodes in the navigation tree
 */
class NodeFunctionContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Functions'), Node::CONTAINER);
        $this->icon = ['image' => 'b_routines', 'title' => __('Functions')];
        $this->links = [
            'text' => ['route' => '/database/routines', 'params' => ['type' => 'FUNCTION', 'db' => null]],
            'icon' => ['route' => '/database/routines', 'params' => ['type' => 'FUNCTION', 'db' => null]],
        ];
        $this->realName = 'functions';

        $newLabel = _pgettext('Create new function', 'New');
        $new = NodeFactory::getInstanceForNewNode($newLabel, 'new_function italics');
        $new->icon = ['image' => 'b_routine_add', 'title' => $newLabel];
        $new->links = [
            'text' => [
                'route' => '/database/routines',
                'params' => ['item_type' => 'FUNCTION', 'add_item' => 1, 'db' => null],
            ],
            'icon' => [
                'route' => '/database/routines',
                'params' => ['item_type' => 'FUNCTION', 'add_item' => 1, 'db' => null],
            ],
        ];
        $this->addChild($new);
    }
}
