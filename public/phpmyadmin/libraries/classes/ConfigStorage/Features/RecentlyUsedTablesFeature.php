<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class RecentlyUsedTablesFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $recent;

    public function __construct(DatabaseName $database, TableName $recent)
    {
        $this->database = $database;
        $this->recent = $recent;
    }
}
