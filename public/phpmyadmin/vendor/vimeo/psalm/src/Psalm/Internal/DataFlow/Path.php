<?php

namespace Psalm\Internal\DataFlow;

/**
 * @psalm-immutable
 */
class Path
{
    public $type;

    public $unescaped_taints;

    public $escaped_taints;

    public $length;

    /**
     * @param ?array<string> $unescaped_taints
     * @param ?array<string> $escaped_taints
     */
    public function __construct(
        string $type,
        int $length,
        ?array $unescaped_taints = null,
        ?array $escaped_taints = null
    ) {
        $this->type = $type;
        $this->length = $length;
        $this->unescaped_taints = $unescaped_taints;
        $this->escaped_taints = $escaped_taints;
    }
}
