<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Export;
use PhpMyAdmin\Util;

/**
 * Schema export handler
 */
class SchemaExportController
{
    /** @var Export */
    private $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    public function __invoke(): void
    {
        if (! isset($_POST['export_type'])) {
            Util::checkParameters(['export_type']);
        }

        /**
         * Include the appropriate Schema Class depending on $export_type
         * default is PDF
         */
        $this->export->processExportSchema($_POST['export_type']);
    }
}
