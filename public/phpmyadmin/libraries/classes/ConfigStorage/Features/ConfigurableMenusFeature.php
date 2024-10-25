<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class ConfigurableMenusFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $userGroups;

    /** @var TableName */
    public $users;

    public function __construct(DatabaseName $database, TableName $userGroups, TableName $users)
    {
        $this->database = $database;
        $this->userGroups = $userGroups;
        $this->users = $users;
    }
}
