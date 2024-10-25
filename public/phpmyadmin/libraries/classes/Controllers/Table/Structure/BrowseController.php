<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function sprintf;

final class BrowseController extends AbstractController
{
    /** @var Sql */
    private $sql;

    public function __construct(ResponseRenderer $response, Template $template, string $db, string $table, Sql $sql)
    {
        parent::__construct($response, $template, $db, $table);
        $this->sql = $sql;
    }

    public function __invoke(): void
    {
        if (empty($_POST['selected_fld'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->displayTableBrowseForSelectedColumns($GLOBALS['goto']);
    }

    /**
     * Function to display table browse for selected columns
     *
     * @param string $goto goto page url
     */
    private function displayTableBrowseForSelectedColumns($goto): void
    {
        $GLOBALS['active_page'] = Url::getFromRoute('/sql');
        $fields = [];
        foreach ($_POST['selected_fld'] as $sval) {
            $fields[] = Util::backquote($sval);
        }

        $sql_query = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $fields),
            Util::backquote($this->db),
            Util::backquote($this->table)
        );

        // Parse and analyze the query
        [$analyzed_sql_results, $this->db] = ParseAnalyze::sqlQuery($sql_query, $this->db);

        $this->response->addHTML(
            $this->sql->executeQueryAndGetQueryResponse(
                $analyzed_sql_results ?? '',
                false, // is_gotofile
                $this->db, // db
                $this->table, // table
                null, // find_real_end
                null, // sql_query_for_bookmark
                null, // extra_data
                null, // message_to_show
                null, // sql_data
                $goto, // goto
                null, // disp_query
                null, // disp_message
                $sql_query, // sql_query
                null // complete_query
            )
        );
    }
}
