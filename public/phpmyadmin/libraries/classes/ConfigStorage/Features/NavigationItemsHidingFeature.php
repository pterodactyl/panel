<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class NavigationItemsHidingFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $navigationHiding;

    public function __construct(DatabaseName $database, TableName $navigationHiding)
    {
        $this->database = $database;
        $this->navigationHiding = $navigationHiding;
    }
}
