<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use function __;
use function strlen;
use function substr;

/**
 * Represents a columns node in the navigation tree
 */
class NodeColumn extends Node
{
    /**
     * Initialises the class
     *
     * @param array $item    array to identify the column node
     * @param int   $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool  $isGroup Whether this object has been created
     *                       while grouping nodes
     */
    public function __construct($item, $type = Node::OBJECT, $isGroup = false)
    {
        $this->displayName = $this->getDisplayName($item);

        parent::__construct($item['name'], $type, $isGroup);
        $this->icon = ['image' => $this->getColumnIcon($item['key']), 'title' => __('Column')];
        $this->links = [
            'text' => [
                'route' => '/table/structure/change',
                'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
            ],
            'icon' => [
                'route' => '/table/structure/change',
                'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
            ],
            'title' => __('Structure'),
        ];
        $this->urlParamName = 'field';
    }

    /**
     * Get customized Icon for columns in navigation tree
     *
     * @param string $key The key type - (primary, foreign etc.)
     *
     * @return string Icon name for required key.
     */
    private function getColumnIcon($key)
    {
        switch ($key) {
            case 'PRI':
                $retval = 'b_primary';
                break;
            case 'UNI':
                $retval = 'bd_primary';
                break;
            default:
                $retval = 'pause';
                break;
        }

        return $retval;
    }

    /**
     * Get displayable name for navigation tree (key_type, data_type, default)
     *
     * @param array<string, mixed> $item Item is array containing required info
     *
     * @return string Display name for navigation tree
     */
    private function getDisplayName($item)
    {
        $retval = $item['name'];
        $flag = 0;
        foreach ($item as $key => $value) {
            if (empty($value) || $key === 'name') {
                continue;
            }

            $flag == 0 ? $retval .= ' (' : $retval .= ', ';
            $flag = 1;
            $retval .= $this->getTruncateValue($key, $value);
        }

        return $retval . ')';
    }

    /**
     * Get truncated value for display in node column view
     *
     * @param string $key   key to identify default,datatype etc
     * @param string $value value corresponding to key
     *
     * @return string truncated value
     */
    public function getTruncateValue($key, $value)
    {
        $retval = '';

        switch ($key) {
            case 'default':
                strlen($value) > 6 ?
                    $retval .= substr($value, 0, 6) . '...' :
                    $retval = $value;
                break;
            default:
                $retval = $value;
                break;
        }

        return $retval;
    }
}
