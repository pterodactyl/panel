<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;

/**
 * Index column wrapper
 */
class IndexColumn
{
    /** @var string The column name */
    private $name = '';

    /** @var int The column sequence number in the index, starting with 1. */
    private $seqInIndex = 1;

    /** @var string|null How the column is sorted in the index. "A" (Ascending) or NULL (Not sorted) */
    private $collation = null;

    /**
     * The number of indexed characters if the column is only partly indexed,
     * NULL if the entire column is indexed.
     *
     * @var int|null
     */
    private $subPart = null;

    /**
     * Contains YES if the column may contain NULL.
     * If not, the column contains NO.
     *
     * @var string
     */
    private $null = '';

    /**
     * An estimate of the number of unique values in the index. This is updated
     * by running ANALYZE TABLE or myisamchk -a. Cardinality is counted based on
     * statistics stored as integers, so the value is not necessarily exact even
     * for small tables. The higher the cardinality, the greater the chance that
     * MySQL uses the index when doing joins.
     *
     * @var int|null
     */
    private $cardinality = null;

    /**
     * If the Index uses an expression and not a name
     *
     * @var string|null
     */
    private $expression = null;

    /**
     * @param array $params an array containing the parameters of the index column
     */
    public function __construct(array $params = [])
    {
        $this->set($params);
    }

    /**
     * If the Index has an expression
     */
    public function hasExpression(): bool
    {
        return $this->expression !== null;
    }

    /**
     * The Index expression if it has one
     */
    public function getExpression(): ?string
    {
        return $this->expression;
    }

    /**
     * Sets parameters of the index column
     *
     * @param array $params an array containing the parameters of the index column
     */
    public function set(array $params): void
    {
        if (isset($params['Column_name'])) {
            $this->name = $params['Column_name'];
        }

        if (isset($params['Seq_in_index'])) {
            $this->seqInIndex = $params['Seq_in_index'];
        }

        if (isset($params['Collation'])) {
            $this->collation = $params['Collation'];
        }

        if (isset($params['Cardinality'])) {
            $this->cardinality = $params['Cardinality'];
        }

        if (isset($params['Sub_part'])) {
            $this->subPart = $params['Sub_part'];
        }

        if (isset($params['Expression'])) {
            $this->expression = $params['Expression'];
        }

        if (! isset($params['Null'])) {
            return;
        }

        $this->null = $params['Null'];
    }

    /**
     * Returns the column name
     *
     * @return string column name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the column collation
     *
     * @return string|null column collation
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * Returns the cardinality of the column
     *
     * @return int|null cardinality of the column
     */
    public function getCardinality()
    {
        return $this->cardinality;
    }

    /**
     * Returns whether the column is nullable
     *
     * @param bool $as_text whether to returned the string representation
     *
     * @return string nullability of the column. True/false or Yes/No depending
     *                on the value of the $as_text parameter
     */
    public function getNull($as_text = false): string
    {
        if ($as_text) {
            if (! $this->null || $this->null === 'NO') {
                return __('No');
            }

            return __('Yes');
        }

        return $this->null;
    }

    /**
     * Returns the sequence number of the column in the index
     *
     * @return int sequence number of the column in the index
     */
    public function getSeqInIndex()
    {
        return $this->seqInIndex;
    }

    /**
     * Returns the number of indexed characters if the column is only
     * partly indexed
     *
     * @return int|null the number of indexed characters
     */
    public function getSubPart()
    {
        return $this->subPart;
    }

    /**
     * Gets the properties in an array for comparison purposes
     *
     * @return array an array containing the properties of the index column
     */
    public function getCompareData()
    {
        return [
            'Column_name' => $this->name,
            'Seq_in_index' => $this->seqInIndex,
            'Collation' => $this->collation,
            'Sub_part' => $this->subPart,
            'Null' => $this->null,
        ];
    }
}
