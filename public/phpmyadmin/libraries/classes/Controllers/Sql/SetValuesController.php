<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

use function __;
use function explode;

final class SetValuesController extends AbstractController
{
    /** @var Sql */
    private $sql;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Sql $sql,
        CheckUserPrivileges $checkUserPrivileges
    ) {
        parent::__construct($response, $template);
        $this->sql = $sql;
        $this->checkUserPrivileges = $checkUserPrivileges;
    }

    /**
     * Get possible values for SET fields during grid edit.
     */
    public function __invoke(): void
    {
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $column = $_POST['column'];
        $currentValue = $_POST['curr_value'];
        $fullValues = $_POST['get_full_values'] ?? false;
        $whereClause = $_POST['where_clause'] ?? null;

        $values = $this->sql->getValuesForColumn($db, $table, $column);

        if ($values === null) {
            $this->response->addJSON('message', __('Error in processing request'));
            $this->response->setRequestStatus(false);

            return;
        }

        // If the $currentValue was truncated, we should fetch the correct full values from the table.
        if ($fullValues && ! empty($whereClause)) {
            $currentValue = $this->sql->getFullValuesForSetColumn($db, $table, $column, $whereClause);
        }

        $select = $this->template->render('sql/set_column', [
            'values' => $values,
            'current_values' => explode(',', $currentValue),
        ]);

        $this->response->addJSON('select', $select);
    }
}
