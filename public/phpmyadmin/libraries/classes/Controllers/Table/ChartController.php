<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function htmlspecialchars;
use function json_encode;
use function min;
use function strlen;

/**
 * Handles creation of the chart.
 */
class ChartController extends AbstractController
{
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
        global $db, $table, $cfg, $sql_query, $errorUrl;

        if (isset($_REQUEST['pos'], $_REQUEST['session_max_rows']) && $this->response->isAjax()) {
            $this->ajax();

            return;
        }

        // Throw error if no sql query is set
        if (! isset($sql_query) || $sql_query == '') {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))->getDisplay()
            );

            return;
        }

        $this->addScriptFiles([
            'chart.js',
            'table/chart.js',
            'vendor/jqplot/jquery.jqplot.js',
            'vendor/jqplot/plugins/jqplot.barRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasTextRenderer.js',
            'vendor/jqplot/plugins/jqplot.categoryAxisRenderer.js',
            'vendor/jqplot/plugins/jqplot.dateAxisRenderer.js',
            'vendor/jqplot/plugins/jqplot.pointLabels.js',
            'vendor/jqplot/plugins/jqplot.pieRenderer.js',
            'vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js',
            'vendor/jqplot/plugins/jqplot.highlighter.js',
        ]);

        $url_params = [];

        /**
         * Runs common work
         */
        if (strlen($table) > 0) {
            Util::checkParameters(['db', 'table']);

            $url_params = ['db' => $db, 'table' => $table];
            $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $errorUrl .= Url::getCommon($url_params, '&');

            DbTableExists::check();

            $url_params['goto'] = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $url_params['back'] = Url::getFromRoute('/table/sql');
            $this->dbi->selectDb($db);
        } elseif (strlen($db) > 0) {
            $url_params['goto'] = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            $url_params['back'] = Url::getFromRoute('/sql');

            Util::checkParameters(['db']);

            $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            $errorUrl .= Url::getCommon(['db' => $db], '&');

            if (! $this->hasDatabase()) {
                return;
            }
        } else {
            $url_params['goto'] = Util::getScriptNameForOption($cfg['DefaultTabServer'], 'server');
            $url_params['back'] = Url::getFromRoute('/sql');
            $errorUrl = Url::getFromRoute('/');

            if ($this->dbi->isSuperUser()) {
                $this->dbi->selectDb('mysql');
            }
        }

        $result = $this->dbi->tryQuery($sql_query);
        $fields_meta = $row = [];
        if ($result !== false) {
            $fields_meta = $this->dbi->getFieldsMeta($result);
            $row = $result->fetchAssoc();
        }

        $keys = array_keys($row);
        $numericColumnFound = false;
        foreach (array_keys($keys) as $idx) {
            if (
                isset($fields_meta[$idx]) && (
                $fields_meta[$idx]->isType(FieldMetadata::TYPE_INT)
                || $fields_meta[$idx]->isType(FieldMetadata::TYPE_REAL)
                )
            ) {
                $numericColumnFound = true;
                break;
            }
        }

        if (! $numericColumnFound) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                __('No numeric columns present in the table to plot.')
            );

            return;
        }

        $url_params['db'] = $db;
        $url_params['reload'] = 1;

        $startAndNumberOfRowsFieldset = Generator::getStartAndNumberOfRowsFieldsetData($sql_query);

        /**
         * Displays the page
         */
        $this->render('table/chart/tbl_chart', [
            'url_params' => $url_params,
            'keys' => $keys,
            'fields_meta' => $fields_meta,
            'table_has_a_numeric_column' => $numericColumnFound,
            'start_and_number_of_rows_fieldset' => $startAndNumberOfRowsFieldset,
        ]);
    }

    /**
     * Handle ajax request
     */
    public function ajax(): void
    {
        global $db, $table, $sql_query, $urlParams, $errorUrl, $cfg;

        if (strlen($table) > 0 && strlen($db) > 0) {
            Util::checkParameters(['db', 'table']);

            $urlParams = ['db' => $db, 'table' => $table];
            $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $errorUrl .= Url::getCommon($urlParams, '&');

            DbTableExists::check();
        }

        $parser = new Parser($sql_query);
        /**
         * @var SelectStatement $statement
         */
        $statement = $parser->statements[0];
        if (empty($statement->limit)) {
            $statement->limit = new Limit($_REQUEST['session_max_rows'], $_REQUEST['pos']);
        } else {
            $start = $statement->limit->offset + $_REQUEST['pos'];
            $rows = min($_REQUEST['session_max_rows'], $statement->limit->rowCount - $_REQUEST['pos']);
            $statement->limit = new Limit($rows, $start);
        }

        $sql_with_limit = $statement->build();

        $result = $this->dbi->tryQuery($sql_with_limit);
        $data = [];
        if ($result !== false) {
            $data = $result->fetchAllAssoc();
        }

        if ($data === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No data to display'));

            return;
        }

        $sanitized_data = [];

        foreach ($data as $data_row) {
            $tmp_row = [];
            foreach ($data_row as $data_column => $data_value) {
                $escaped_value = $data_value === null ? null : htmlspecialchars($data_value);
                $tmp_row[htmlspecialchars($data_column)] = $escaped_value;
            }

            $sanitized_data[] = $tmp_row;
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON('message', null);
        $this->response->addJSON('chartData', json_encode($sanitized_data));
    }
}
