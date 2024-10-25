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
 * Represents a container for procedure nodes in the navigation tree
 */
class NodeProcedureContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Procedures'), Node::CONTAINER);
        $this->icon = ['image' => 'b_routines', 'title' => __('Procedures')];
        $this->links = [
            'text' => ['route' => '/database/routines', 'params' => ['type' => 'PROCEDURE', 'db' => null]],
            'icon' => ['route' => '/database/routines', 'params' => ['type' => 'PROCEDURE', 'db' => null]],
        ];
        $this->realName = 'procedures';

        $newLabel = _pgettext('Create new procedure', 'New');
        $new = NodeFactory::getInstanceForNewNode($newLabel, 'new_procedure italics');
        $new->icon = ['image' => 'b_routine_add', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/database/routines', 'params' => ['add_item' => 1, 'db' => null]],
            'icon' => ['route' => '/database/routines', 'params' => ['add_item' => 1, 'db' => null]],
        ];
        $this->addChild($new);
    }
}
