<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function is_array;

final class ExportRowsController extends AbstractController
{
    /** @var ExportController */
    private $exportController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        ExportController $exportController
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->exportController = $exportController;
    }

    public function __invoke(): void
    {
        global $active_page, $single_table, $where_clause;

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        // Needed to allow SQL export
        $single_table = true;

        // As we got the rows to be exported from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $where_clause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            foreach ($_POST['rows_to_delete'] as $i_where_clause) {
                $where_clause[] = $i_where_clause;
            }
        }

        $active_page = Url::getFromRoute('/table/export');

        ($this->exportController)();
    }
}
