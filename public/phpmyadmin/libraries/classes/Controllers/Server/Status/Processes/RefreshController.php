<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;

final class RefreshController extends AbstractController
{
    /** @var Processes */
    private $processes;

    public function __construct(ResponseRenderer $response, Template $template, Data $data, Processes $processes)
    {
        parent::__construct($response, $template, $data);
        $this->processes = $processes;
    }

    public function __invoke(): void
    {
        $params = [
            'showExecuting' => $_POST['showExecuting'] ?? null,
            'full' => $_POST['full'] ?? null,
            'column_name' => $_POST['column_name'] ?? null,
            'order_by_field' => $_POST['order_by_field'] ?? null,
            'sort_order' => $_POST['sort_order'] ?? null,
        ];

        if (! $this->response->isAjax()) {
            return;
        }

        $this->render('server/status/processes/list', $this->processes->getList($params));
    }
}
