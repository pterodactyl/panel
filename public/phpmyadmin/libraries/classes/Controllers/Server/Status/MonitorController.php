<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\SysInfo\SysInfo;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function is_numeric;
use function microtime;

class MonitorController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, Data $data, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template, $data);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $errorUrl;

        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->addScriptFiles([
            'vendor/jquery/jquery.tablesorter.js',
            'jquery.sortable-table.js',
            'vendor/jqplot/jquery.jqplot.js',
            'vendor/jqplot/plugins/jqplot.pieRenderer.js',
            'vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasTextRenderer.js',
            'vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js',
            'vendor/jqplot/plugins/jqplot.dateAxisRenderer.js',
            'vendor/jqplot/plugins/jqplot.highlighter.js',
            'vendor/jqplot/plugins/jqplot.cursor.js',
            'jqplot/plugins/jqplot.byteFormatter.js',
            'server/status/monitor.js',
            'server/status/sorter.js',
            'chart.js',// Needed by createProfilingChart in server/status/monitor.js
        ]);

        $form = [
            'server_time' => (int) (microtime(true) * 1000),
            'server_os' => SysInfo::getOs(),
            'is_superuser' => $this->dbi->isSuperUser(),
            'server_db_isLocal' => $this->data->dbIsLocal,
        ];

        $javascriptVariableNames = [];
        foreach ($this->data->status as $name => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $javascriptVariableNames[] = $name;
        }

        $this->render('server/status/monitor/index', [
            'javascript_variable_names' => $javascriptVariableNames,
            'form' => $form,
        ]);
    }
}
