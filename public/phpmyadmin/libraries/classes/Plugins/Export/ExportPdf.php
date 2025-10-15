<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use TCPDF;

use function __;
use function class_exists;
use function extension_loaded;

/**
 * Produce a PDF report (export) from a query
 */
class ExportPdf extends ExportPlugin
{
    /**
     * PhpMyAdmin\Plugins\Export\Helpers\Pdf instance
     *
     * @var Pdf
     */
    private $pdf;

    /**
     * PDF Report Title
     *
     * @var string
     */
    private $pdfReportTitle = '';

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'pdf';
    }

    /**
     * Initialize the local variables that are used for export PDF.
     */
    protected function init(): void
    {
        if (! empty($_POST['pdf_report_title'])) {
            $this->pdfReportTitle = $_POST['pdf_report_title'];
        }

        $this->setPdf(new Pdf('L', 'pt', 'A3'));
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('PDF');
        $exportPluginProperties->setExtension('pdf');
        $exportPluginProperties->setMimeType('application/pdf');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new TextPropertyItem(
            'report_title',
            __('Report title:')
        );
        $generalOptions->addProperty($leaf);
        // add the group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // what to dump (structure/data/both) main group
        $dumpWhat = new OptionsPropertyMainGroup(
            'dump_what',
            __('Dump table')
        );
        $leaf = new RadioPropertyItem('structure_or_data');
        $leaf->setValues(
            [
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data'),
            ]
        );
        $dumpWhat->addProperty($leaf);
        // add the group to the root group
        $exportSpecificOptions->addProperty($dumpWhat);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        $pdf = $this->getPdf();
        $pdf->Open();

        $pdf->setTitleFontSize(18);
        $pdf->setTitleText($this->pdfReportTitle);
        $pdf->setTopMargin(30);

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $pdf = $this->getPdf();

        // instead of $pdf->Output():
        return $this->export->outputHandler($pdf->getPDFData());
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter($db): bool
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate($db, $exportType, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        $pdf = $this->getPdf();
        $pdf->setCurrentDb($db);
        $pdf->setCurrentTable($table);
        $pdf->setDbAlias($db_alias);
        $pdf->setTableAlias($table_alias);
        $pdf->setAliases($aliases);
        $pdf->setPurpose(__('Dumping data'));
        $pdf->mysqlReport($sqlQuery);

        return true;
    }

    /**
     * Outputs result of raw query in PDF format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     * @param string      $crlf     the end of line sequence
     */
    public function exportRawQuery(string $errorUrl, ?string $db, string $sqlQuery, string $crlf): bool
    {
        global $dbi;

        $pdf = $this->getPdf();
        $pdf->setDbAlias('----');
        $pdf->setTableAlias('----');
        $pdf->setPurpose(__('Query result data'));

        if ($db !== null) {
            $pdf->setCurrentDb($db);
            $dbi->selectDb($db);
        }

        $pdf->mysqlReport($sqlQuery);

        return true;
    }

    /**
     * Outputs table structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $errorUrl    the url to go back in case of error
     * @param string $exportMode  'create_table', 'triggers', 'create_view',
     *                             'stand_in'
     * @param string $exportType  'server', 'database', 'table'
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column
     *                            comments as comments in the structure;
     *                            this is deprecated but the parameter is
     *                            left here because /export calls
     *                            PMA_exportStructure() also for other
     *                            export types which use this parameter
     * @param bool   $do_mime     whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     * @param array  $aliases     aliases for db/table/columns
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $exportMode,
        $exportType,
        $do_relation = false,
        $do_comments = false,
        $do_mime = false,
        $dates = false,
        array $aliases = []
    ): bool {
        $db_alias = $db;
        $table_alias = $table;
        $purpose = '';
        $this->initAlias($aliases, $db_alias, $table_alias);
        $pdf = $this->getPdf();
        // getting purpose to show at top
        switch ($exportMode) {
            case 'create_table':
                $purpose = __('Table structure');
                break;
            case 'triggers':
                $purpose = __('Triggers');
                break;
            case 'create_view':
                $purpose = __('View structure');
                break;
            case 'stand_in':
                $purpose = __('Stand in');
        }

        $pdf->setCurrentDb($db);
        $pdf->setCurrentTable($table);
        $pdf->setDbAlias($db_alias);
        $pdf->setTableAlias($table_alias);
        $pdf->setAliases($aliases);
        $pdf->setPurpose($purpose);

        /**
         * comment display set true as presently in pdf
         * format, no option is present to take user input.
         */
        $do_comments = true;
        switch ($exportMode) {
            case 'create_table':
                $pdf->getTableDef($db, $table, $do_relation, $do_comments, $do_mime, false, $aliases);
                break;
            case 'triggers':
                $pdf->getTriggers($db, $table);
                break;
            case 'create_view':
                $pdf->getTableDef($db, $table, $do_relation, $do_comments, $do_mime, false, $aliases);
                break;
            case 'stand_in':
                /* export a stand-in definition to resolve view dependencies
                 * Yet to develop this function
                 * $pdf->getTableDefStandIn($db, $table, $crlf);
                 */
        }

        return true;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the PhpMyAdmin\Plugins\Export\Helpers\Pdf instance
     *
     * @return Pdf
     */
    private function getPdf()
    {
        return $this->pdf;
    }

    /**
     * Instantiates the PhpMyAdmin\Plugins\Export\Helpers\Pdf class
     *
     * @param Pdf $pdf The instance
     */
    private function setPdf($pdf): void
    {
        $this->pdf = $pdf;
    }

    public static function isAvailable(): bool
    {
        return class_exists(TCPDF::class) && extension_loaded('curl');
    }
}
