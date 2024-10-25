<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;

final class RelationalValuesController extends AbstractController
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
     * Get values for the relational columns
     *
     * During grid edit, if we have a relational field, show the dropdown for it.
     */
    public function __invoke(): void
    {
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $column = $_POST['column'];
        if (
            $_SESSION['tmpval']['relational_display'] === 'D'
            && isset($_POST['relation_key_or_display_column'])
            && $_POST['relation_key_or_display_column']
        ) {
            $curr_value = $_POST['relation_key_or_display_column'];
        } else {
            $curr_value = $_POST['curr_value'];
        }

        $dropdown = $this->sql->getHtmlForRelationalColumnDropdown($db, $table, $column, $curr_value);
        $this->response->addJSON('dropdown', $dropdown);
    }
}
