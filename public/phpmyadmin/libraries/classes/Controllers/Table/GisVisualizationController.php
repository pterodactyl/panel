<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function is_array;

/**
 * Handles creation of the GIS visualizations.
 */
final class GisVisualizationController extends AbstractController
{
    /** @var GisVisualization */
    private $visualization;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cfg, $urlParams, $db, $errorUrl;

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        // SQL query for retrieving GIS data
        $sqlQuery = '';
        if (isset($_GET['sql_query'], $_GET['sql_signature'])) {
            if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
                $sqlQuery = $_GET['sql_query'];
            }
        } elseif (isset($_POST['sql_query'])) {
            $sqlQuery = $_POST['sql_query'];
        }

        // Throw error if no sql query is set
        if ($sqlQuery == '') {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))->getDisplay()
            );

            return;
        }

        // Execute the query and return the result
        $result = $this->dbi->tryQuery($sqlQuery);
        // Get the meta data of results
        $meta = [];
        if ($result !== false) {
            $meta = $this->dbi->getFieldsMeta($result);
        }

        // Find the candidate fields for label column and spatial column
        $labelCandidates = [];
        $spatialCandidates = [];
        foreach ($meta as $column_meta) {
            if ($column_meta->isMappedTypeGeometry) {
                $spatialCandidates[] = $column_meta->name;
            } else {
                $labelCandidates[] = $column_meta->name;
            }
        }

        // Get settings if any posted
        $visualizationSettings = [];
        // Download as PNG/SVG/PDF use _GET and the normal form uses _POST
        if (isset($_POST['visualizationSettings']) && is_array($_POST['visualizationSettings'])) {
            $visualizationSettings = $_POST['visualizationSettings'];
        } elseif (isset($_GET['visualizationSettings']) && is_array($_GET['visualizationSettings'])) {
            $visualizationSettings = $_GET['visualizationSettings'];
        }

        // Check mysql version
        $visualizationSettings['mysqlVersion'] = $this->dbi->getVersion();
        $visualizationSettings['isMariaDB'] = $this->dbi->isMariaDB();

        if (! isset($visualizationSettings['labelColumn']) && isset($labelCandidates[0])) {
            $visualizationSettings['labelColumn'] = '';
        }

        // If spatial column is not set, use first geometric column as spatial column
        if (! isset($visualizationSettings['spatialColumn'])) {
            $visualizationSettings['spatialColumn'] = $spatialCandidates[0];
        }

        // Download as PNG/SVG/PDF use _GET and the normal form uses _POST
        // Convert geometric columns from bytes to text.
        $pos = (int) ($_POST['pos'] ?? $_GET['pos'] ?? $_SESSION['tmpval']['pos']);
        if (isset($_POST['session_max_rows']) || isset($_GET['session_max_rows'])) {
            $rows = (int) ($_POST['session_max_rows'] ?? $_GET['session_max_rows']);
        } else {
            if ($_SESSION['tmpval']['max_rows'] !== 'all') {
                $rows = (int) $_SESSION['tmpval']['max_rows'];
            } else {
                $rows = (int) $GLOBALS['cfg']['MaxRows'];
            }
        }

        $this->visualization = GisVisualization::get($sqlQuery, $visualizationSettings, $rows, $pos);

        if (isset($_GET['saveToFile'])) {
            $this->saveToFile($visualizationSettings['spatialColumn'], $_GET['fileFormat']);

            return;
        }

        $this->addScriptFiles(['vendor/openlayers/OpenLayers.js', 'table/gis_visualization.js']);

        // If all the rows contain SRID, use OpenStreetMaps on the initial loading.
        if (! isset($_POST['displayVisualization'])) {
            if ($this->visualization->hasSrid()) {
                $visualizationSettings['choice'] = 'useBaseLayer';
            } else {
                unset($visualizationSettings['choice']);
            }
        }

        $this->visualization->setUserSpecifiedSettings($visualizationSettings);
        if ($visualizationSettings != null) {
            foreach ($this->visualization->getSettings() as $setting => $val) {
                if (isset($visualizationSettings[$setting])) {
                    continue;
                }

                $visualizationSettings[$setting] = $val;
            }
        }

        /**
         * Displays the page
         */
        $urlParams['goto'] = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $urlParams['back'] = Url::getFromRoute('/sql');
        $urlParams['sql_query'] = $sqlQuery;
        $urlParams['sql_signature'] = Core::signSqlQuery($sqlQuery);
        $downloadUrl = Url::getFromRoute('/table/gis-visualization', array_merge(
            $urlParams,
            [
                'saveToFile' => true,
                'session_max_rows' => $rows,
                'pos' => $pos,
                'visualizationSettings[spatialColumn]' => $visualizationSettings['spatialColumn'],
                'visualizationSettings[labelColumn]' => $visualizationSettings['labelColumn'] ?? null,
            ]
        ));

        $startAndNumberOfRowsFieldset = Generator::getStartAndNumberOfRowsFieldsetData($sqlQuery);

        $html = $this->template->render('table/gis_visualization/gis_visualization', [
            'url_params' => $urlParams,
            'download_url' => $downloadUrl,
            'label_candidates' => $labelCandidates,
            'spatial_candidates' => $spatialCandidates,
            'visualization_settings' => $visualizationSettings,
            'start_and_number_of_rows_fieldset' => $startAndNumberOfRowsFieldset,
            'visualization' => $this->visualization->toImage('svg'),
            'draw_ol' => $this->visualization->asOl(),
        ]);

        $this->response->addHTML($html);
    }

    /**
     * @param string $filename File name
     * @param string $format   Save format
     */
    private function saveToFile(string $filename, string $format): void
    {
        $this->response->disable();
        $this->visualization->toFile($filename, $format);
    }
}
