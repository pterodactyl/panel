<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table\Maintenance;

use function is_string;

/**
 * @psalm-immutable
 */
final class Message
{
    /** @var string */
    public $table;

    /** @var string */
    public $operation;

    /** @var string */
    public $type;

    /** @var string */
    public $text;

    private function __construct(string $table, string $operation, string $type, string $text)
    {
        $this->table = $table;
        $this->operation = $operation;
        $this->type = $type;
        $this->text = $text;
    }

    /**
     * @param mixed[] $row
     */
    public static function fromArray(array $row): self
    {
        $table = isset($row['Table']) && is_string($row['Table']) ? $row['Table'] : '';
        $operation = isset($row['Op']) && is_string($row['Op']) ? $row['Op'] : '';
        $type = isset($row['Msg_type']) && is_string($row['Msg_type']) ? $row['Msg_type'] : '';
        $text = isset($row['Msg_text']) && is_string($row['Msg_text']) ? $row['Msg_text'] : '';

        return new self($table, $operation, $type, $text);
    }
}
