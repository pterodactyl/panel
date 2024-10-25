<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Stringable;

use function in_array;
use function is_numeric;
use function is_string;

/**
 * @see https://mariadb.com/kb/en/show-warnings/
 * @see https://dev.mysql.com/doc/refman/en/show-warnings.html
 *
 * @psalm-immutable
 */
final class Warning implements Stringable
{
    /**
     * @var string
     * @psalm-var 'Note'|'Warning'|'Error'|'?'
     */
    public $level;

    /**
     * @var int
     * @psalm-var 0|positive-int
     */
    public $code;

    /** @var string */
    public $message;

    private function __construct(string $level, int $code, string $message)
    {
        $this->level = in_array($level, ['Note', 'Warning', 'Error'], true) ? $level : '?';
        $this->code = $code >= 1 ? $code : 0;
        $this->message = $message;
    }

    /**
     * @param mixed[] $row
     */
    public static function fromArray(array $row): self
    {
        $level = '';
        $code = 0;
        $message = '';

        if (isset($row['Level']) && is_string($row['Level'])) {
            $level = $row['Level'];
        }

        if (isset($row['Code']) && is_numeric($row['Code'])) {
            $code = (int) $row['Code'];
        }

        if (isset($row['Message']) && is_string($row['Message'])) {
            $message = $row['Message'];
        }

        return new self($level, $code, $message);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return $this->level . ': #' . $this->code . ($this->message !== '' ? ' ' . $this->message : '');
    }
}
