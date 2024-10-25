<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\ExportController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

final class TablesController extends AbstractController
{
    /** @var ExportController */
    private $exportController;

    public function __construct(ResponseRenderer $response, Template $template, ExportController $exportController)
    {
        parent::__construct($response, $template);
        $this->exportController = $exportController;
    }

    public function __invoke(): void
    {
        if (empty($_POST['selected_tbl'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        ($this->exportController)();
    }
}
