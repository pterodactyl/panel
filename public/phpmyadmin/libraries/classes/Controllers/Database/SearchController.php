<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\Search;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

class SearchController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, string $db, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cfg, $db, $errorUrl, $urlParams, $tables, $num_tables, $total_num_tables, $sub_part;
        global $tooltip_truename, $tooltip_aliasname, $pos;

        $this->addScriptFiles(['database/search.js', 'sql.js', 'makegrid.js']);

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        // If config variable $cfg['UseDbSearch'] is on false : exit.
        if (! $cfg['UseDbSearch']) {
            Generator::mysqlDie(
                __('Access denied!'),
                '',
                false,
                $errorUrl
            );
        }

        $urlParams['goto'] = Url::getFromRoute('/database/search');

        // Create a database search instance
        $databaseSearch = new Search($this->dbi, $db, $this->template);

        // Display top links if we are not in an Ajax request
        if (! $this->response->isAjax()) {
            [
                $tables,
                $num_tables,
                $total_num_tables,
                $sub_part,,,
                $tooltip_truename,
                $tooltip_aliasname,
                $pos,
            ] = Util::getDbInfo($db, $sub_part ?? '');
        }

        // Main search form has been submitted, get results
        if (isset($_POST['submit_search'])) {
            $this->response->addHTML($databaseSearch->getSearchResults());
        }

        // If we are in an Ajax request, we need to exit after displaying all the HTML
        if ($this->response->isAjax() && empty($_REQUEST['ajax_page_request'])) {
            return;
        }

        // Display the search form
        $this->response->addHTML($databaseSearch->getMainHtml());
    }
}
