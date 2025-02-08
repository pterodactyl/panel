<?php

declare(strict_types=1);

namespace PhpMyAdmin\Partitioning;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Table;
use PhpMyAdmin\Util;

use function __;
use function sprintf;

final class Maintenance
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    public function analyze(DatabaseName $db, TableName $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s ANALYZE PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    public function check(DatabaseName $db, TableName $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s CHECK PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    public function drop(DatabaseName $db, TableName $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s DROP PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($query);

        return [(bool) $result, $query];
    }

    public function optimize(DatabaseName $db, TableName $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s OPTIMIZE PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    /**
     * @return array<int, bool|string>
     * @psalm-return array{bool, string}
     */
    public function rebuild(DatabaseName $db, TableName $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s REBUILD PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($query);

        return [(bool) $result, $query];
    }

    public function repair(DatabaseName $db, TableName $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s REPAIR PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    /**
     * @return array<int, bool|string>
     * @psalm-return array{bool, string}
     */
    public function truncate(DatabaseName $db, TableName $table, string $partition): array
    {
        if (Table::get($table->getName(), $db->getName(), $this->dbi)->isView()) {
            return [false, __('This table is a view, it can not be truncated.')];
        }

        $query = sprintf(
            'ALTER TABLE %s TRUNCATE PARTITION %s;',
            Util::backquote($table->getName()),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($query);

        return [(bool) $result, $query];
    }
}
