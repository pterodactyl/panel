<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class ColumnCommentsFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $columnInfo;

    public function __construct(DatabaseName $database, TableName $columnInfo)
    {
        $this->database = $database;
        $this->columnInfo = $columnInfo;
    }
}
