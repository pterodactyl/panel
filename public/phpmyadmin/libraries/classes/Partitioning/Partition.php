<?php
/**
 * Library for extracting information about the partitions
 */

declare(strict_types=1);

namespace PhpMyAdmin\Partitioning;

use function array_values;

/**
 * base Partition Class
 */
class Partition extends SubPartition
{
    /** @var string partition description */
    protected $description;
    /** @var SubPartition[] sub partitions */
    protected $subPartitions = [];

    /**
     * Loads data from the fetched row from information_schema.PARTITIONS
     *
     * @param array $row fetched row
     */
    protected function loadData(array $row): void
    {
        $this->name = $row['PARTITION_NAME'];
        $this->ordinal = $row['PARTITION_ORDINAL_POSITION'];
        $this->method = $row['PARTITION_METHOD'];
        $this->expression = $row['PARTITION_EXPRESSION'];
        $this->description = $row['PARTITION_DESCRIPTION'];
        // no sub partitions, load all data to this object
        if (! empty($row['SUBPARTITION_NAME'])) {
            return;
        }

        $this->loadCommonData($row);
    }

    /**
     * Returns the partition description
     *
     * @return string partition description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add a sub partition
     *
     * @param SubPartition $partition Sub partition
     */
    public function addSubPartition(SubPartition $partition): void
    {
        $this->subPartitions[] = $partition;
    }

    /**
     * Whether there are sub partitions
     */
    public function hasSubPartitions(): bool
    {
        return ! empty($this->subPartitions);
    }

    /**
     * Returns the number of data rows
     *
     * @return int number of rows
     */
    public function getRows()
    {
        if (empty($this->subPartitions)) {
            return $this->rows;
        }

        $rows = 0;
        foreach ($this->subPartitions as $subPartition) {
            $rows += $subPartition->rows;
        }

        return $rows;
    }

    /**
     * Returns the total data length
     *
     * @return int data length
     */
    public function getDataLength()
    {
        if (empty($this->subPartitions)) {
            return $this->dataLength;
        }

        $dataLength = 0;
        foreach ($this->subPartitions as $subPartition) {
            $dataLength += $subPartition->dataLength;
        }

        return $dataLength;
    }

    /**
     * Returns the total index length
     *
     * @return int index length
     */
    public function getIndexLength()
    {
        if (empty($this->subPartitions)) {
            return $this->indexLength;
        }

        $indexLength = 0;
        foreach ($this->subPartitions as $subPartition) {
            $indexLength += $subPartition->indexLength;
        }

        return $indexLength;
    }

    /**
     * Returns the list of sub partitions
     *
     * @return SubPartition[]
     */
    public function getSubPartitions()
    {
        return $this->subPartitions;
    }

    /**
     * Returns array of partitions for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return Partition[]
     */
    public static function getPartitions($db, $table)
    {
        global $dbi;

        if (self::havePartitioning()) {
            $result = $dbi->fetchResult(
                'SELECT * FROM `information_schema`.`PARTITIONS`'
                . " WHERE `TABLE_SCHEMA` = '" . $dbi->escapeString($db)
                . "' AND `TABLE_NAME` = '" . $dbi->escapeString($table) . "'"
            );
            if ($result) {
                $partitionMap = [];
                /** @var array $row */
                foreach ($result as $row) {
                    if (empty($row['PARTITION_NAME'])) {
                        continue;
                    }

                    $partition = $partitionMap[$row['PARTITION_NAME']] ?? new Partition($row);
                    $partitionMap[$row['PARTITION_NAME']] = $partition;

                    if (empty($row['SUBPARTITION_NAME'])) {
                        continue;
                    }

                    $partition->addSubPartition(new SubPartition($row));
                }

                return array_values($partitionMap);
            }

            return [];
        }

        return [];
    }

    /**
     * returns array of partition names for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return array   of partition names
     */
    public static function getPartitionNames($db, $table)
    {
        global $dbi;

        if (self::havePartitioning()) {
            return $dbi->fetchResult(
                'SELECT DISTINCT `PARTITION_NAME` FROM `information_schema`.`PARTITIONS`'
                . " WHERE `TABLE_SCHEMA` = '" . $dbi->escapeString($db)
                . "' AND `TABLE_NAME` = '" . $dbi->escapeString($table) . "'"
            );
        }

        return [];
    }

    /**
     * returns the partition method used by the table.
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string|null partition method
     */
    public static function getPartitionMethod($db, $table)
    {
        global $dbi;

        if (self::havePartitioning()) {
            $partition_method = $dbi->fetchResult(
                'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS`'
                . " WHERE `TABLE_SCHEMA` = '" . $dbi->escapeString($db) . "'"
                . " AND `TABLE_NAME` = '" . $dbi->escapeString($table) . "'"
                . ' LIMIT 1'
            );
            if (! empty($partition_method)) {
                return $partition_method[0];
            }
        }

        return null;
    }

    /**
     * checks if MySQL server supports partitioning
     *
     * @static
     * @staticvar bool $have_partitioning
     * @staticvar bool $already_checked
     */
    public static function havePartitioning(): bool
    {
        global $dbi;

        static $have_partitioning = false;
        static $already_checked = false;

        if (! $already_checked) {
            if ($dbi->getVersion() < 50600) {
                if ($dbi->fetchValue('SELECT @@have_partitioning;')) {
                    $have_partitioning = true;
                }
            } elseif ($dbi->getVersion() >= 80000) {
                $have_partitioning = true;
            } else {
                // see https://dev.mysql.com/doc/refman/5.6/en/partitioning.html
                $plugins = $dbi->fetchResult('SHOW PLUGINS');
                foreach ($plugins as $value) {
                    if ($value['Name'] === 'partition') {
                        $have_partitioning = true;
                        break;
                    }
                }
            }

            $already_checked = true;
        }

        return $have_partitioning;
    }
}
