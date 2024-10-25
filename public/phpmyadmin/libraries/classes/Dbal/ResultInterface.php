<?php
/**
 * Extension independent database result interface
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Generator;
use IteratorAggregate;
use PhpMyAdmin\FieldMetadata;

/**
 * Extension independent database result interface
 *
 * @extends IteratorAggregate<array<string, (string|null)>>
 */
interface ResultInterface extends IteratorAggregate
{
    /**
     * Returns a generator that traverses through the whole result set
     * and returns each row as an associative array
     *
     * @psalm-return Generator<int, array<string, string|null>, mixed, void>
     */
    public function getIterator(): Generator;

    /**
     * Returns the next row of the result with associative keys
     *
     * @return array<string,string|null>
     */
    public function fetchAssoc(): array;

    /**
     * Returns the next row of the result with numeric keys
     *
     * @return array<int,string|null>
     */
    public function fetchRow(): array;

    /**
     * Returns a single value from the given result; false on error
     *
     * @param int|string $field
     *
     * @return string|false|null
     */
    public function fetchValue($field = 0);

    /**
     * Returns all rows of the result
     *
     * @return array<int, array<string,string|null>>
     */
    public function fetchAllAssoc(): array;

    /**
     * Returns values from the first column of each row
     *
     * @return array<int, string|null>
     */
    public function fetchAllColumn(): array;

    /**
     * Returns values as single dimensional array where the key is the first column
     * and the value is the second column,
     * e.g. "SELECT id, name FROM users"
     * produces: ['123' => 'John', '124' => 'Jane']
     *
     * @return array<string, string|null>
     */
    public function fetchAllKeyPair(): array;

    /**
     * Returns the number of fields in the result
     */
    public function numFields(): int;

    /**
     * Returns the number of rows in the result
     *
     * @return string|int
     * @psalm-return int|numeric-string
     */
    public function numRows();

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param int $offset offset to seek
     *
     * @return bool True if the offset exists, false otherwise
     */
    public function seek(int $offset): bool;

    /**
     * Returns meta info for fields in $result
     *
     * @return array<int, FieldMetadata> meta info for fields in $result
     */
    public function getFieldsMeta(): array;

    /**
     * Returns the names of the fields in the result
     *
     * @return array<int, string> Fields names
     */
    public function getFieldNames(): array;
}
