<?php
/**
 * Extension independent database result
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Generator;
use mysqli_result;
use PhpMyAdmin\FieldMetadata;
use Webmozart\Assert\Assert;

use function array_column;
use function is_array;
use function is_bool;
use function is_string;
use function method_exists;

use const MYSQLI_ASSOC;

/**
 * Extension independent database result
 */
final class MysqliResult implements ResultInterface
{
    /**
     * The result identifier produced by the DBiExtension
     *
     * @var mysqli_result|null $result
     */
    private $result;

    /**
     * @param mysqli_result|bool $result
     */
    public function __construct($result)
    {
        $this->result = is_bool($result) ? null : $result;
    }

    /**
     * Returns a generator that traverses through the whole result set
     * and returns each row as an associative array
     *
     * @psalm-return Generator<int, array<string, string|null>, mixed, void>
     */
    public function getIterator(): Generator
    {
        if (! $this->result) {
            return;
        }

        $this->result->data_seek(0);
        /** @var array<string, string|null> $row */
        foreach ($this->result as $row) {
            yield $row;
        }
    }

    /**
     * Returns the next row of the result with associative keys
     *
     * @return array<string,string|null>
     */
    public function fetchAssoc(): array
    {
        if (! $this->result) {
            return [];
        }

        $row = $this->result->fetch_assoc();

        return is_array($row) ? $row : [];
    }

    /**
     * Returns the next row of the result with numeric keys
     *
     * @return array<int,string|null>
     */
    public function fetchRow(): array
    {
        if (! $this->result) {
            return [];
        }

        $row = $this->result->fetch_row();

        return is_array($row) ? $row : [];
    }

    /**
     * Returns a single value from the given result; false on error
     *
     * @param int|string $field
     *
     * @return string|false|null
     */
    public function fetchValue($field = 0)
    {
        if (is_string($field)) {
            $row = $this->fetchAssoc();
        } else {
            $row = $this->fetchRow();
        }

        return $row[$field] ?? false;
    }

    /**
     * Returns all rows of the result
     *
     * @return array<int, array<string,string|null>>
     */
    public function fetchAllAssoc(): array
    {
        if (! $this->result) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->result->data_seek(0);

        // Pre PHP 8.1 when compiled against libmysql doesn't support fetch_all
        if (method_exists($this->result, 'fetch_all')) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }

        $rows = [];
        while ($row = $this->result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Returns values from the first column of each row
     *
     * @return array<int, string|null>
     */
    public function fetchAllColumn(): array
    {
        if (! $this->result) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->result->data_seek(0);

        // Pre PHP 8.1 when compiled against libmysql doesn't support fetch_all
        if (method_exists($this->result, 'fetch_all')) {
            return array_column($this->result->fetch_all(), 0);
        }

        $rows = [];
        while ($row = $this->result->fetch_row()) {
            $rows[] = $row[0];
        }

        return $rows;
    }

    /**
     * Returns values as single dimensional array where the key is the first column
     * and the value is the second column, e.g.
     * SELECT id, name FROM users
     * produces: ['123' => 'John', '124' => 'Jane']
     *
     * @return array<string, string|null>
     */
    public function fetchAllKeyPair(): array
    {
        if (! $this->result) {
            return [];
        }

        Assert::greaterThanEq($this->result->field_count, 2);

        // This function should return all rows, not only the remaining rows
        $this->result->data_seek(0);

        // Pre PHP 8.1 when compiled against libmysql doesn't support fetch_all
        if (method_exists($this->result, 'fetch_all')) {
            return array_column($this->result->fetch_all(), 1, 0);
        }

        $rows = [];
        while ($row = $this->result->fetch_row()) {
            $rows[$row[0] ?? ''] = $row[1];
        }

        return $rows;
    }

    /**
     * Returns the number of fields in the result
     */
    public function numFields(): int
    {
        if (! $this->result) {
            return 0;
        }

        return $this->result->field_count;
    }

    /**
     * Returns the number of rows in the result
     *
     * @return string|int
     * @psalm-return int|numeric-string
     */
    public function numRows()
    {
        if (! $this->result) {
            return 0;
        }

        return $this->result->num_rows;
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param int $offset offset to seek
     *
     * @return bool True if the offset exists, false otherwise
     */
    public function seek(int $offset): bool
    {
        if (! $this->result) {
            return false;
        }

        return $this->result->data_seek($offset);
    }

    /**
     * returns meta info for fields in $result
     *
     * @return array<int, FieldMetadata> meta info for fields in $result
     */
    public function getFieldsMeta(): array
    {
        if (! $this->result) {
            return [];
        }

        $fields = [];
        foreach ($this->result->fetch_fields() as $k => $field) {
            $fields[$k] = new FieldMetadata($field->type, $field->flags, $field);
        }

        return $fields;
    }

    /**
     * Returns the names of the fields in the result
     *
     * @return array<int, string> Fields names
     */
    public function getFieldNames(): array
    {
        if (! $this->result) {
            return [];
        }

        /** @var list<string> $column */
        $column = array_column($this->result->fetch_fields(), 'name');

        return $column;
    }
}
