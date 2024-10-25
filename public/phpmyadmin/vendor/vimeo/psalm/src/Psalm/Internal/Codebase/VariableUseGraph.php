<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;

use function abs;
use function array_merge;
use function count;

class VariableUseGraph extends DataFlowGraph
{
    /** @var array<string, array<string, true>> */
    protected $backward_edges = [];

    /** @var array<string, DataFlowNode> */
    private $nodes = [];

    /** @var array<string, list<CodeLocation>> */
    private $origin_locations_by_id = [];

    public function addNode(DataFlowNode $node): void
    {
        $this->nodes[$node->id] = $node;
    }

    /**
     * @param array<string> $added_taints
     * @param array<string> $removed_taints
     */
    public function addPath(
        DataFlowNode $from,
        DataFlowNode $to,
        string $path_type,
        ?array $added_taints = null,
        ?array $removed_taints = null
    ): void {
        $from_id = $from->id;
        $to_id = $to->id;

        if ($from_id === $to_id) {
            return;
        }

        $length = 0;

        if ($from->code_location
            && $to->code_location
            && $from->code_location->file_path === $to->code_location->file_path
        ) {
            $to_line = $to->code_location->raw_line_number;
            $from_line = $from->code_location->raw_line_number;
            $length = abs($to_line - $from_line);
        }

        $this->backward_edges[$to_id][$from_id] = true;
        $this->forward_edges[$from_id][$to_id] = new Path($path_type, $length);
    }

    public function isVariableUsed(DataFlowNode $assignment_node): bool
    {
        $visited_source_ids = [];

        $sources = [$assignment_node];

        for ($i = 0; count($sources) && $i < 200; $i++) {
            $new_child_nodes = [];

            foreach ($sources as $source) {
                $visited_source_ids[$source->id] = true;

                $child_nodes = $this->getChildNodes(
                    $source,
                    $visited_source_ids
                );

                if ($child_nodes === null) {
                    return true;
                }

                $new_child_nodes = array_merge(
                    $new_child_nodes,
                    $child_nodes
                );
            }

            $sources = $new_child_nodes;
        }

        return false;
    }

    /**
     * @return list<CodeLocation>
     */
    public function getOriginLocations(DataFlowNode $assignment_node): array
    {
        if (isset($this->origin_locations_by_id[$assignment_node->id])) {
            return $this->origin_locations_by_id[$assignment_node->id];
        }

        $visited_child_ids = [];

        $origin_locations = [];

        $child_nodes = [$assignment_node];

        for ($i = 0; count($child_nodes) && $i < 200; $i++) {
            $new_parent_nodes = [];

            foreach ($child_nodes as $child_node) {
                $visited_child_ids[$child_node->id] = true;

                $parent_nodes = $this->getParentNodes(
                    $child_node,
                    $visited_child_ids
                );

                if (!$parent_nodes) {
                    if ($child_node->code_location) {
                        $origin_locations[] = $child_node->code_location;
                    }

                    continue;
                }

                $new_parent_nodes = array_merge(
                    $new_parent_nodes,
                    $parent_nodes
                );
            }

            $child_nodes = $new_parent_nodes;
        }

        $this->origin_locations_by_id[$assignment_node->id] = $origin_locations;

        return $origin_locations;
    }

    /**
     * @param array<string, bool> $visited_source_ids
     * @return array<string, DataFlowNode>|null
     */
    private function getChildNodes(
        DataFlowNode $generated_source,
        array $visited_source_ids
    ): ?array {
        $new_child_nodes = [];

        if (!isset($this->forward_edges[$generated_source->id])) {
            return [];
        }

        foreach ($this->forward_edges[$generated_source->id] as $to_id => $path) {
            $path_type = $path->type;

            if ($path->type === 'variable-use'
                || $path->type === 'closure-use'
                || $path->type === 'global-use'
                || $path->type === 'use-inside-instance-property'
                || $path->type === 'use-inside-static-property'
                || $path->type === 'use-inside-call'
                || $path->type === 'use-inside-conditional'
                || $path->type === 'use-inside-isset'
                || $path->type === 'arg'
            ) {
                return null;
            }

            if (isset($visited_source_ids[$to_id])) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'arraykey', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'arrayvalue', $generated_source->path_types)) {
                continue;
            }

            if (self::shouldIgnoreFetch($path_type, 'property', $generated_source->path_types)) {
                continue;
            }

            $new_destination = new DataFlowNode($to_id, $to_id, null);
            $new_destination->path_types = array_merge($generated_source->path_types, [$path_type]);

            $new_child_nodes[$to_id] = $new_destination;
        }

        return $new_child_nodes;
    }

    /**
     * @param array<string, bool> $visited_source_ids
     * @return list<DataFlowNode>
     */
    private function getParentNodes(
        DataFlowNode $destination,
        array $visited_source_ids
    ): array {
        $new_parent_nodes = [];

        if (!isset($this->backward_edges[$destination->id])) {
            return [];
        }

        foreach ($this->backward_edges[$destination->id] as $from_id => $_) {
            if (isset($visited_source_ids[$from_id])) {
                continue;
            }

            if (isset($this->nodes[$from_id])) {
                $new_parent_nodes[] = $this->nodes[$from_id];
            }
        }

        return $new_parent_nodes;
    }
}
