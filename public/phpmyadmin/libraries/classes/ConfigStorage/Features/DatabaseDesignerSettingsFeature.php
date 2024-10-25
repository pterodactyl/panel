<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class DatabaseDesignerSettingsFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $designerSettings;

    public function __construct(DatabaseName $database, TableName $designerSettings)
    {
        $this->database = $database;
        $this->designerSettings = $designerSettings;
    }
}
