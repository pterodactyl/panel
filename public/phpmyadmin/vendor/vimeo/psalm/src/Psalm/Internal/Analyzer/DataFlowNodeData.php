<?php

namespace Psalm\Internal\Analyzer;

/**
 * @psalm-immutable
 */
class DataFlowNodeData
{
    /**
     * @var int
     */
    public $line_from;

    /**
     * @var int
     */
    public $line_to;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $snippet;

    /**
     * @var int
     */
    public $from;

    /**
     * @var int
     */
    public $to;

    /**
     * @var int
     */
    public $snippet_from;

    /**
     * @var int
     */
    public $column_from;

    /**
     * @var int
     */
    public $column_to;

    public function __construct(
        string $label,
        int $line_from,
        int $line_to,
        string $file_name,
        string $file_path,
        string $snippet,
        int $from,
        int $to,
        int $snippet_from,
        int $column_from,
        int $column_to
    ) {
        $this->label = $label;
        $this->line_from = $line_from;
        $this->line_to = $line_to;
        $this->file_name = $file_name;
        $this->file_path = $file_path;
        $this->snippet = $snippet;
        $this->from = $from;
        $this->to = $to;
        $this->snippet_from = $snippet_from;
        $this->column_from = $column_from;
        $this->column_to = $column_to;
    }
}
