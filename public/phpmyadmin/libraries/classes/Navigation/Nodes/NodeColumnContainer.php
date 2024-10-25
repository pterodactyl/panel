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
 * Represents a container for column nodes in the navigation tree
 */
class NodeColumnContainer extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Columns'), Node::CONTAINER);
        $this->icon = ['image' => 'pause', 'title' => __('Columns')];
        $this->links = [
            'text' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
            'icon' => ['route' => '/table/structure', 'params' => ['db' => null, 'table' => null]],
        ];
        $this->realName = 'columns';

        $newLabel = _pgettext('Create new column', 'New');
        $new = NodeFactory::getInstanceForNewNode($newLabel, 'new_column italics');
        $new->icon = ['image' => 'b_column_add', 'title' => $newLabel];
        $new->links = [
            'text' => [
                'route' => '/table/add-field',
                'params' => ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
            ],
            'icon' => [
                'route' => '/table/add-field',
                'params' => ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
            ],
        ];
        $this->addChild($new);
    }
}
