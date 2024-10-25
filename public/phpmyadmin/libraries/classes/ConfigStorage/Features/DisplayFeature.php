<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class DisplayFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $relation;

    /** @var TableName */
    public $tableInfo;

    public function __construct(DatabaseName $database, TableName $relation, TableName $tableInfo)
    {
        $this->database = $database;
        $this->relation = $relation;
        $this->tableInfo = $tableInfo;
    }
}
