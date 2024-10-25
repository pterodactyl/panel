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
 * Represents a container for table nodes in the navigation tree
 */
class NodeTableContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Tables'), Node::CONTAINER);
        $this->icon = ['image' => 'b_browse', 'title' => __('Tables')];
        $this->links = [
            'text' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'table', 'db' => null]],
            'icon' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'table', 'db' => null]],
        ];
        $this->realName = 'tables';
        $this->classes = 'tableContainer subContainer';

        $newLabel = _pgettext('Create new table', 'New');
        $new = NodeFactory::getInstanceForNewNode($newLabel, 'new_table italics');
        $new->icon = ['image' => 'b_table_add', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/table/create', 'params' => ['db' => null]],
            'icon' => ['route' => '/table/create', 'params' => ['db' => null]],
        ];
        $this->addChild($new);
    }
}
