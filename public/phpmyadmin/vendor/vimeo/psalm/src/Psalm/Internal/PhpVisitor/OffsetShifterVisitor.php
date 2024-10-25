<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * Shifts all nodes in a given AST by a set amount
 */
class OffsetShifterVisitor extends PhpParser\NodeVisitorAbstract
{
    /** @var int */
    private $file_offset;

    /** @var int */
    private $line_offset;

    /** @var array<int, int> */
    private $extra_offsets;

    /**
     * @param array<int, int> $extra_offsets
     */
    public function __construct(int $offset, int $line_offset, array $extra_offsets)
    {
        $this->file_offset = $offset;
        $this->line_offset = $line_offset;
        $this->extra_offsets = $extra_offsets;
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        /** @var array{startFilePos: int, endFilePos: int, startLine: int} */
        $attrs = $node->getAttributes();

        if ($cs = $node->getComments()) {
            $new_comments = [];

            foreach ($cs as $c) {
                if ($c instanceof PhpParser\Comment\Doc) {
                    $new_comments[] = new PhpParser\Comment\Doc(
                        $c->getText(),
                        $c->getStartLine() + $this->line_offset,
                        $c->getStartFilePos() + $this->file_offset + ($this->extra_offsets[$c->getStartFilePos()] ?? 0)
                    );
                } else {
                    $new_comments[] = new PhpParser\Comment(
                        $c->getText(),
                        $c->getStartLine() + $this->line_offset,
                        $c->getStartFilePos() + $this->file_offset + ($this->extra_offsets[$c->getStartFilePos()] ?? 0)
                    );
                }
            }

            $node->setAttribute('comments', $new_comments);
        }

        $node->setAttribute(
            'startFilePos',
            $attrs['startFilePos'] + $this->file_offset + ($this->extra_offsets[$attrs['startFilePos']] ?? 0)
        );
        $node->setAttribute(
            'endFilePos',
            $attrs['endFilePos'] + $this->file_offset + ($this->extra_offsets[$attrs['endFilePos']] ?? 0)
        );
        $node->setAttribute('startLine', $attrs['startLine'] + $this->line_offset);

        return null;
    }
}
