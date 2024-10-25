<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

class ProcessesController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Processes */
    private $processes;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        DatabaseInterface $dbi,
        Processes $processes
    ) {
        parent::__construct($response, $template, $data);
        $this->dbi = $dbi;
        $this->processes = $processes;
    }

    public function __invoke(): void
    {
        global $errorUrl;

        $params = [
            'showExecuting' => $_POST['showExecuting'] ?? null,
            'full' => $_POST['full'] ?? null,
            'column_name' => $_POST['column_name'] ?? null,
            'order_by_field' => $_POST['order_by_field'] ?? null,
            'sort_order' => $_POST['sort_order'] ?? null,
        ];
        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->addScriptFiles(['server/status/processes.js']);

        $isChecked = false;
        if (! empty($params['showExecuting'])) {
            $isChecked = true;
        }

        $urlParams = [
            'ajax_request' => true,
            'full' => $params['full'] ?? '',
            'column_name' => $params['column_name'] ?? '',
            'order_by_field' => $params['order_by_field'] ?? '',
            'sort_order' => $params['sort_order'] ?? '',
        ];

        $listHtml = $this->template->render('server/status/processes/list', $this->processes->getList($params));

        $this->render('server/status/processes/index', [
            'url_params' => $urlParams,
            'is_checked' => $isChecked,
            'server_process_list' => $listHtml,
        ]);
    }
}
