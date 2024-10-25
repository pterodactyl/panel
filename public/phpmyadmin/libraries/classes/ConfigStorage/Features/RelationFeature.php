<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class RelationFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $relation;

    public function __construct(DatabaseName $database, TableName $relation)
    {
        $this->database = $database;
        $this->relation = $relation;
    }
}
