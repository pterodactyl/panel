<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function define;
use function defined;
use function property_exists;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_ENUM_FLAG;
use const MYSQLI_MULTIPLE_KEY_FLAG;
use const MYSQLI_NOT_NULL_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_SET_FLAG;
use const MYSQLI_TYPE_BIT;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_DOUBLE;
use const MYSQLI_TYPE_ENUM;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_INT24;
use const MYSQLI_TYPE_JSON;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_LONG_BLOB;
use const MYSQLI_TYPE_LONGLONG;
use const MYSQLI_TYPE_MEDIUM_BLOB;
use const MYSQLI_TYPE_NEWDATE;
use const MYSQLI_TYPE_NEWDECIMAL;
use const MYSQLI_TYPE_NULL;
use const MYSQLI_TYPE_SET;
use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TIME;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_TINY;
use const MYSQLI_TYPE_TINY_BLOB;
use const MYSQLI_TYPE_VAR_STRING;
use const MYSQLI_TYPE_YEAR;
use const MYSQLI_UNIQUE_KEY_FLAG;
use const MYSQLI_UNSIGNED_FLAG;
use const MYSQLI_ZEROFILL_FLAG;

/**
 * Handles fields Metadata
 *
 * NOTE: Getters are not used in all implementations due to the important cost of getters calls
 */
final class FieldMetadata
{
    public const TYPE_GEOMETRY = 1;
    public const TYPE_BIT = 2;
    public const TYPE_JSON = 3;
    public const TYPE_REAL = 4;
    public const TYPE_INT = 5;
    public const TYPE_BLOB = 6;
    public const TYPE_UNKNOWN = -1;
    public const TYPE_NULL = 7;
    public const TYPE_STRING = 8;
    public const TYPE_DATE = 9;
    public const TYPE_TIME = 10;
    public const TYPE_TIMESTAMP = 11;
    public const TYPE_DATETIME = 12;
    public const TYPE_YEAR = 13;

    /**
     * @var bool
     * @readonly
     */
    public $isMultipleKey;


    /**
     * @var bool
     * @readonly
     */
    public $isPrimaryKey;


    /**
     * @var bool
     * @readonly
     */
    public $isUniqueKey;


    /**
     * @var bool
     * @readonly
     */
    public $isNotNull;


    /**
     * @var bool
     * @readonly
     */
    public $isUnsigned;


    /**
     * @var bool
     * @readonly
     */
    public $isZerofill;


    /**
     * @var bool
     * @readonly
     */
    public $isNumeric;


    /**
     * @var bool
     * @readonly
     */
    public $isBlob;


    /**
     * @var bool
     * @readonly
     */
    public $isBinary;


    /**
     * @var bool
     * @readonly
     */
    public $isEnum;


    /**
     * @var bool
     * @readonly
     */
    public $isSet;

    /** @var int|null */
    private $mappedType;


    /**
     * @var bool
     * @readonly
     */
    public $isMappedTypeBit;

    /**
     * @var bool
     * @readonly
     */
    public $isMappedTypeGeometry;

    /**
     * @var bool
     * @readonly
     */
    public $isMappedTypeTimestamp;

    /**
     * The column name
     *
     * @var string
     */
    public $name;

    /**
     * The original column name if an alias did exist
     *
     * @var string
     */
    public $orgname;

    /**
     * The table name
     *
     * @var string
     */
    public $table;

    /**
     * The original table name
     *
     * @var string
     */
    public $orgtable;

    /**
     * The charset number
     *
     * @readonly
     * @var int
     */
    public $charsetnr;

    /**
     * The number of decimals used (for integer fields)
     *
     * @readonly
     * @var int
     */
    public $decimals;

    /**
     * The width of the field, as specified in the table definition.
     *
     * @readonly
     * @var int
     */
    public $length;

    /**
     * A field only used by the Results class
     *
     * @var string
     */
    public $internalMediaType;

    public function __construct(int $fieldType, int $fieldFlags, object $field)
    {
        $this->mappedType = $this->getTypeMap()[$fieldType] ?? null;

        $this->isMultipleKey = (bool) ($fieldFlags & MYSQLI_MULTIPLE_KEY_FLAG);
        $this->isPrimaryKey = (bool) ($fieldFlags & MYSQLI_PRI_KEY_FLAG);
        $this->isUniqueKey = (bool) ($fieldFlags & MYSQLI_UNIQUE_KEY_FLAG);
        $this->isNotNull = (bool) ($fieldFlags & MYSQLI_NOT_NULL_FLAG);
        $this->isUnsigned = (bool) ($fieldFlags & MYSQLI_UNSIGNED_FLAG);
        $this->isZerofill = (bool) ($fieldFlags & MYSQLI_ZEROFILL_FLAG);
        $this->isBlob = (bool) ($fieldFlags & MYSQLI_BLOB_FLAG);
        $this->isEnum = (bool) ($fieldFlags & MYSQLI_ENUM_FLAG);
        $this->isSet = (bool) ($fieldFlags & MYSQLI_SET_FLAG);

        // as flags 32768 can be NUM_FLAG or GROUP_FLAG
        // reference: https://www.php.net/manual/en/mysqli-result.fetch-fields.php
        // so check field type instead of flags
        $this->isNumeric = $this->isType(self::TYPE_INT) || $this->isType(self::TYPE_REAL);

        /*
            MYSQLI_PART_KEY_FLAG => 'part_key',
            MYSQLI_TIMESTAMP_FLAG => 'timestamp',
            MYSQLI_AUTO_INCREMENT_FLAG => 'auto_increment',
        */

        $this->isMappedTypeBit = $this->isType(self::TYPE_BIT);
        $this->isMappedTypeGeometry = $this->isType(self::TYPE_GEOMETRY);
        $this->isMappedTypeTimestamp = $this->isType(self::TYPE_TIMESTAMP);

        $this->name = property_exists($field, 'name') ? $field->name : '';
        $this->orgname = property_exists($field, 'orgname') ? $field->orgname : '';
        $this->table = property_exists($field, 'table') ? $field->table : '';
        $this->orgtable = property_exists($field, 'orgtable') ? $field->orgtable : '';
        $this->charsetnr = property_exists($field, 'charsetnr') ? $field->charsetnr : -1;
        $this->decimals = property_exists($field, 'decimals') ? $field->decimals : 0;
        $this->length = property_exists($field, 'length') ? $field->length : 0;

        // 63 is the number for the MySQL charset "binary"
        $this->isBinary = (
            $fieldType === MYSQLI_TYPE_TINY_BLOB || $fieldType === MYSQLI_TYPE_BLOB
            || $fieldType === MYSQLI_TYPE_MEDIUM_BLOB || $fieldType === MYSQLI_TYPE_LONG_BLOB
            || $fieldType === MYSQLI_TYPE_VAR_STRING || $fieldType === MYSQLI_TYPE_STRING
        ) && $this->charsetnr == 63;
    }

    /**
     * @see https://dev.mysql.com/doc/connectors/en/apis-php-mysqli.constants.html
     */
    private function getTypeMap(): array
    {
        // Issue #16043 - client API mysqlnd seem not to have MYSQLI_TYPE_JSON defined
        if (! defined('MYSQLI_TYPE_JSON')) {
            define('MYSQLI_TYPE_JSON', 245);
        }

        // Build an associative array for a type look up
        $typeAr = [];
        $typeAr[MYSQLI_TYPE_DECIMAL] = self::TYPE_REAL;
        $typeAr[MYSQLI_TYPE_NEWDECIMAL] = self::TYPE_REAL;
        $typeAr[MYSQLI_TYPE_BIT] = self::TYPE_INT;
        $typeAr[MYSQLI_TYPE_TINY] = self::TYPE_INT;
        $typeAr[MYSQLI_TYPE_SHORT] = self::TYPE_INT;
        $typeAr[MYSQLI_TYPE_LONG] = self::TYPE_INT;
        $typeAr[MYSQLI_TYPE_FLOAT] = self::TYPE_REAL;
        $typeAr[MYSQLI_TYPE_DOUBLE] = self::TYPE_REAL;
        $typeAr[MYSQLI_TYPE_NULL] = self::TYPE_NULL;
        $typeAr[MYSQLI_TYPE_TIMESTAMP] = self::TYPE_TIMESTAMP;
        $typeAr[MYSQLI_TYPE_LONGLONG] = self::TYPE_INT;
        $typeAr[MYSQLI_TYPE_INT24] = self::TYPE_INT;
        $typeAr[MYSQLI_TYPE_DATE] = self::TYPE_DATE;
        $typeAr[MYSQLI_TYPE_TIME] = self::TYPE_TIME;
        $typeAr[MYSQLI_TYPE_DATETIME] = self::TYPE_DATETIME;
        $typeAr[MYSQLI_TYPE_YEAR] = self::TYPE_YEAR;
        $typeAr[MYSQLI_TYPE_NEWDATE] = self::TYPE_DATE;
        $typeAr[MYSQLI_TYPE_ENUM] = self::TYPE_UNKNOWN;
        $typeAr[MYSQLI_TYPE_SET] = self::TYPE_UNKNOWN;
        $typeAr[MYSQLI_TYPE_TINY_BLOB] = self::TYPE_BLOB;
        $typeAr[MYSQLI_TYPE_MEDIUM_BLOB] = self::TYPE_BLOB;
        $typeAr[MYSQLI_TYPE_LONG_BLOB] = self::TYPE_BLOB;
        $typeAr[MYSQLI_TYPE_BLOB] = self::TYPE_BLOB;
        $typeAr[MYSQLI_TYPE_VAR_STRING] = self::TYPE_STRING;
        $typeAr[MYSQLI_TYPE_STRING] = self::TYPE_STRING;
        // MySQL returns MYSQLI_TYPE_STRING for CHAR
        // and MYSQLI_TYPE_CHAR === MYSQLI_TYPE_TINY
        // so this would override TINYINT and mark all TINYINT as string
        // see https://github.com/phpmyadmin/phpmyadmin/issues/8569
        //$typeAr[MYSQLI_TYPE_CHAR]        = self::TYPE_STRING;
        $typeAr[MYSQLI_TYPE_GEOMETRY] = self::TYPE_GEOMETRY;
        $typeAr[MYSQLI_TYPE_BIT] = self::TYPE_BIT;
        $typeAr[MYSQLI_TYPE_JSON] = self::TYPE_JSON;

        return $typeAr;
    }

    public function isNotNull(): bool
    {
        return $this->isNotNull;
    }

    public function isNumeric(): bool
    {
        return $this->isNumeric;
    }

    public function isBinary(): bool
    {
        return $this->isBinary;
    }

    public function isBlob(): bool
    {
        return $this->isBlob;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function isUniqueKey(): bool
    {
        return $this->isUniqueKey;
    }

    public function isMultipleKey(): bool
    {
        return $this->isMultipleKey;
    }

    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    public function isZerofill(): bool
    {
        return $this->isZerofill;
    }

    public function isEnum(): bool
    {
        return $this->isEnum;
    }

    public function isSet(): bool
    {
        return $this->isSet;
    }

    /**
     * Checks that it is type DATE/TIME/DATETIME
     */
    public function isDateTimeType(): bool
    {
        return $this->isType(self::TYPE_DATE)
            || $this->isType(self::TYPE_TIME)
            || $this->isType(self::TYPE_DATETIME)
            || $this->isType(self::TYPE_TIMESTAMP);
    }

    /**
     * Checks that it contains time
     * A "DATE" field returns false for example
     */
    public function isTimeType(): bool
    {
        return $this->isType(self::TYPE_TIME)
            || $this->isType(self::TYPE_TIMESTAMP)
            || $this->isType(self::TYPE_DATETIME);
    }

    /**
     * Get the mapped type as a string
     *
     * @return string Empty when nothing could be matched
     */
    public function getMappedType(): string
    {
        $types = [
            self::TYPE_GEOMETRY => 'geometry',
            self::TYPE_BIT => 'bit',
            self::TYPE_JSON => 'json',
            self::TYPE_REAL => 'real',
            self::TYPE_INT => 'int',
            self::TYPE_BLOB => 'blob',
            self::TYPE_UNKNOWN => 'unknown',
            self::TYPE_NULL => 'null',
            self::TYPE_STRING => 'string',
            self::TYPE_DATE => 'date',
            self::TYPE_TIME => 'time',
            self::TYPE_TIMESTAMP => 'timestamp',
            self::TYPE_DATETIME => 'datetime',
            self::TYPE_YEAR => 'year',
        ];

        return $types[$this->mappedType] ?? '';
    }

    /**
     * Check if it is the mapped type
     *
     * @phpstan-param self::TYPE_* $type
     */
    public function isType(int $type): bool
    {
        return $this->mappedType === $type;
    }

    /**
     * Check if it is NOT the mapped type
     *
     * @phpstan-param self::TYPE_* $type
     */
    public function isNotType(int $type): bool
    {
        return $this->mappedType !== $type;
    }
}
