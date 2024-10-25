<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class TrackingFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $tracking;

    public function __construct(DatabaseName $database, TableName $tracking)
    {
        $this->database = $database;
        $this->tracking = $tracking;
    }
}
