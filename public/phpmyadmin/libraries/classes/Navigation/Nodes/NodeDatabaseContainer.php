<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Navigation\NodeFactory;

use function _pgettext;

/**
 * Represents a container for database nodes in the navigation tree
 */
class NodeDatabaseContainer extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name An identifier for the new node
     */
    public function __construct($name)
    {
        global $dbi;

        $checkUserPrivileges = new CheckUserPrivileges($dbi);
        $checkUserPrivileges->getPrivileges();

        parent::__construct($name, Node::CONTAINER);

        if (! $GLOBALS['is_create_db_priv'] || $GLOBALS['cfg']['ShowCreateDb'] === false) {
            return;
        }

        $newLabel = _pgettext('Create new database', 'New');
        $new = NodeFactory::getInstanceForNewNode($newLabel, 'new_database italics');
        $new->icon = ['image' => 'b_newdb', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/server/databases', 'params' => []],
            'icon' => ['route' => '/server/databases', 'params' => []],
        ];
        $this->addChild($new);
    }
}
