<?php
/**
 * This class is responsible for creating Node objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Navigation\Nodes\Node;

use function __;
use function class_exists;
use function preg_match;
use function sprintf;
use function trigger_error;

use const E_USER_ERROR;
use const E_USER_WARNING;
use const PHP_VERSION_ID;

/**
 * Node factory - instantiates Node objects or objects derived from the Node class
 */
class NodeFactory
{
    /** @var string */
    protected static $namespace = 'PhpMyAdmin\\Navigation\\Nodes\\%s';

    /**
     * Sanitizes the name of a Node class
     *
     * @param string $class The class name to be sanitized
     *
     * @return string
     * @psalm-return class-string
     */
    private static function sanitizeClass($class)
    {
        if (! preg_match('@^Node\w*$@', $class)) {
            $class = 'Node';
            trigger_error(
                sprintf(
                    /* l10n: The word "Node" must not be translated here */
                    __('Invalid class name "%1$s", using default of "Node"'),
                    $class
                ),
                PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING
            );
        }

        return self::checkClass($class);
    }

    /**
     * Checks if a class exists and try to load it.
     * Will return the default class name back if the
     * file for some subclass is not available
     *
     * @param string $class The class name to check
     *
     * @return string
     * @psalm-return class-string
     */
    private static function checkClass($class)
    {
        /** @var class-string $class */
        $class = sprintf(self::$namespace, $class);

        if (! class_exists($class)) {
            /** @var class-string $class */
            $class = sprintf(self::$namespace, 'Node');
            trigger_error(
                sprintf(
                    __('Could not load class "%1$s"'),
                    $class
                ),
                PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING
            );
        }

        return $class;
    }

    /**
     * Instantiates a Node object
     *
     * @param string       $class   The name of the class to instantiate
     * @param string|array $name    An identifier for the new node
     * @param int          $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool         $isGroup Whether this object has been created while grouping nodes
     */
    public static function getInstance(
        $class = 'Node',
        $name = 'default',
        $type = Node::OBJECT,
        $isGroup = false
    ): Node {
        $class = self::sanitizeClass($class);

        /** @var Node $node */
        $node = new $class($name, $type, $isGroup);

        return $node;
    }

    /**
     * Instantiates a Node object that will be used only for "New db/table/etc.." objects
     *
     * @param string $name    An identifier for the new node
     * @param string $classes Extra CSS classes for the node
     */
    public static function getInstanceForNewNode(
        string $name,
        string $classes
    ): Node {
        $node = new Node($name, Node::OBJECT, false);
        $node->title = $name;
        $node->isNew = true;
        $node->classes = $classes;

        return $node;
    }
}
