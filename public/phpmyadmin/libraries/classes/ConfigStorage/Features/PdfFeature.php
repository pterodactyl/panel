<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @psalm-immutable
 */
final class PdfFeature
{
    /** @var DatabaseName */
    public $database;

    /** @var TableName */
    public $pdfPages;

    /** @var TableName */
    public $tableCoords;

    public function __construct(DatabaseName $database, TableName $pdfPages, TableName $tableCoords)
    {
        $this->database = $database;
        $this->pdfPages = $pdfPages;
        $this->tableCoords = $tableCoords;
    }
}
