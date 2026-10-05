<?php

declare(strict_types=1);

namespace Rules\Support;

use PhpParser\Node;

/**
 * Static child access for php-parser nodes. Sub-nodes are the node's public
 * properties, so get_object_vars() reaches them without the dynamic property
 * names (`$node->$name`) this rule set bans.
 */
final class NodeChildren
{
    /**
     * @return list<Node>
     */
    public static function nodes(Node $node): array
    {
        $children = [];
        foreach (get_object_vars($node) as $value) {
            if ($value instanceof Node) {
                $children[] = $value;

                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof Node) {
                        $children[] = $item;
                    }
                }
            }
        }

        return $children;
    }

    /**
     * @return list<array<Node\Stmt>>
     */
    public static function statementLists(Node $node): array
    {
        $lists = [];
        foreach (get_object_vars($node) as $value) {
            if (! is_array($value)) {
                continue;
            }

            $statements = [];
            foreach ($value as $item) {
                if ($item instanceof Node\Stmt) {
                    $statements[] = $item;
                }
            }

            if ($statements !== []) {
                $lists[] = $statements;
            }
        }

        return $lists;
    }
}
