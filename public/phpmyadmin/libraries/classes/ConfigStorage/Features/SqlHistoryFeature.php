<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class SqlHistoryFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $history;

    public function __construct(DatabaseName $database, TableName $history)
    {
        $this->database = $database;
        $this->history = $history;
    }
}
