<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class UserPreferencesFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $userConfig;

    public function __construct(DatabaseName $database, TableName $userConfig)
    {
        $this->database = $database;
        $this->userConfig = $userConfig;
    }
}
