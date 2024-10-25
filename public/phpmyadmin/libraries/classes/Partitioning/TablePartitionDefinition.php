<?php

declare(strict_types=1);

namespace PhpMyAdmin\Partitioning;

use function array_intersect_key;
use function array_merge;
use function array_splice;
use function is_numeric;
use function min;

final class TablePartitionDefinition
{
    /**
     * @param array|null $details Details that may be pre-filled
     *
     * @return array
     */
    public static function getDetails(?array $details = null): array
    {
        if (! isset($details)) {
            $details = self::generateDetails();
        }

        return $details;
    }

    /**
     * @return array
     */
    private static function generateDetails(): array
    {
        $partitionDetails = self::extractDetailsFromRequest();

        // Only LIST and RANGE type parameters allow subpartitioning
        $partitionDetails['can_have_subpartitions'] = $partitionDetails['partition_count'] > 1
            && isset($partitionDetails['partition_by'])
            && ($partitionDetails['partition_by'] === 'RANGE'
                || $partitionDetails['partition_by'] === 'RANGE COLUMNS'
                || $partitionDetails['partition_by'] === 'LIST'
                || $partitionDetails['partition_by'] === 'LIST COLUMNS');

        // Values are specified only for LIST and RANGE type partitions
        $partitionDetails['value_enabled'] = isset($partitionDetails['partition_by'])
            && ($partitionDetails['partition_by'] === 'RANGE'
                || $partitionDetails['partition_by'] === 'RANGE COLUMNS'
                || $partitionDetails['partition_by'] === 'LIST'
                || $partitionDetails['partition_by'] === 'LIST COLUMNS');

        return self::extractPartitions($partitionDetails);
    }

    /**
     * Extract some partitioning and subpartitioning parameters from the request
     *
     * @return array
     */
    private static function extractDetailsFromRequest(): array
    {
        $partitionParams = [
            'partition_by' => null,
            'partition_expr' => null,
            'subpartition_by' => null,
            'subpartition_expr' => null,
        ];
        //Initialize details with values to "null" if not in request
        $details = array_merge(
            $partitionParams,
            //Keep $_POST values, but only for keys that are in $partitionParams
            array_intersect_key($_POST, $partitionParams)
        );

        $details['partition_count'] = self::extractPartitionCount('partition_count') ?: 0;
        $details['subpartition_count'] = self::extractPartitionCount('subpartition_count') ?: 0;

        return $details;
    }

    /**
     * @param string $paramLabel Label searched in request
     */
    private static function extractPartitionCount(string $paramLabel): int
    {
        if (isset($_POST[$paramLabel]) && is_numeric($_POST[$paramLabel])) {
            // MySQL's limit is 8192, so do not allow more
            // @see https://dev.mysql.com/doc/refman/en/partitioning-limitations.html
            $count = min((int) $_POST[$paramLabel], 8192);
        } else {
            $count = 0;
        }

        return $count;
    }

    /**
     * @param array $partitionDetails Details of partitions
     *
     * @return array
     */
    private static function extractPartitions(array $partitionDetails): array
    {
        $partitionCount = $partitionDetails['partition_count'];
        $subpartitionCount = $partitionDetails['subpartition_count'];

        // No partitions
        if ($partitionCount <= 1) {
            return $partitionDetails;
        }

        // Has partitions
        $partitions = $_POST['partitions'] ?? [];

        // Remove details of the additional partitions
        // when number of partitions have been reduced
        array_splice($partitions, $partitionCount);

        for ($i = 0; $i < $partitionCount; $i++) {
            if (! isset($partitions[$i])) { // Newly added partition
                $partitions[$i] = [
                    'name' => 'p' . $i,
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                ];
            }

            $partition =& $partitions[$i];
            $partition['prefix'] = 'partitions[' . $i . ']';

            // Changing from HASH/KEY to RANGE/LIST
            if (! isset($partition['value_type'])) {
                $partition['value_type'] = '';
                $partition['value'] = '';
            }

            if (! isset($partition['engine'])) { // When removing subpartitioning
                $partition['engine'] = '';
                $partition['comment'] = '';
                $partition['data_directory'] = '';
                $partition['index_directory'] = '';
                $partition['max_rows'] = '';
                $partition['min_rows'] = '';
                $partition['tablespace'] = '';
                $partition['node_group'] = '';
            }

            // No subpartitions
            if ($subpartitionCount < 2 || $partitionDetails['can_have_subpartitions'] !== true) {
                unset($partition['subpartitions'], $partition['subpartition_count']);
                continue;
            }

            // Has subpartitions
            $partition['subpartition_count'] = $subpartitionCount;

            if (! isset($partition['subpartitions'])) {
                $partition['subpartitions'] = [];
            }

            $subpartitions =& $partition['subpartitions'];

            // Remove details of the additional subpartitions
            // when number of subpartitions have been reduced
            array_splice($subpartitions, $subpartitionCount);

            for ($j = 0; $j < $subpartitionCount; $j++) {
                if (! isset($subpartitions[$j])) { // Newly added subpartition
                    $subpartitions[$j] = [
                        'name' => $partition['name'] . '_s' . $j,
                        'engine' => '',
                        'comment' => '',
                        'data_directory' => '',
                        'index_directory' => '',
                        'max_rows' => '',
                        'min_rows' => '',
                        'tablespace' => '',
                        'node_group' => '',
                    ];
                }

                $subpartitions[$j]['prefix'] = 'partitions[' . $i . ']'
                    . '[subpartitions][' . $j . ']';
            }
        }

        $partitionDetails['partitions'] = $partitions;

        return $partitionDetails;
    }
}
