<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SavedSearches;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function stripos;

class QueryByExampleController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $savedSearchList, $savedSearch, $currentSearchId;
        global $sql_query, $goto, $sub_part, $tables, $num_tables, $total_num_tables;
        global $tooltip_truename, $tooltip_aliasname, $pos, $urlParams, $cfg, $errorUrl;

        $savedQbeSearchesFeature = $this->relation->getRelationParameters()->savedQueryByExampleSearchesFeature;

        $savedSearchList = [];
        $savedSearch = null;
        $currentSearchId = null;
        $this->addScriptFiles(['database/qbe.js']);
        if ($savedQbeSearchesFeature !== null) {
            //Get saved search list.
            $savedSearch = new SavedSearches();
            $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                ->setDbname($db);

            if (! empty($_POST['searchId'])) {
                $savedSearch->setId($_POST['searchId']);
            }

            //Action field is sent.
            if (isset($_POST['action'])) {
                $savedSearch->setSearchName($_POST['searchName']);
                if ($_POST['action'] === 'create') {
                    $savedSearch->setId(null)
                        ->setCriterias($_POST)
                        ->save($savedQbeSearchesFeature);
                } elseif ($_POST['action'] === 'update') {
                    $savedSearch->setCriterias($_POST)
                        ->save($savedQbeSearchesFeature);
                } elseif ($_POST['action'] === 'delete') {
                    $savedSearch->delete($savedQbeSearchesFeature);
                    //After deletion, reset search.
                    $savedSearch = new SavedSearches();
                    $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                        ->setDbname($db);
                    $_POST = [];
                } elseif ($_POST['action'] === 'load') {
                    if (empty($_POST['searchId'])) {
                        //when not loading a search, reset the object.
                        $savedSearch = new SavedSearches();
                        $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                            ->setDbname($db);
                        $_POST = [];
                    } else {
                        $savedSearch->load($savedQbeSearchesFeature);
                    }
                }
                //Else, it's an "update query"
            }

            $savedSearchList = $savedSearch->getList($savedQbeSearchesFeature);
            $currentSearchId = $savedSearch->getId();
        }

        /**
         * A query has been submitted -> (maybe) execute it
         */
        $hasMessageToDisplay = false;
        if (isset($_POST['submit_sql']) && ! empty($sql_query)) {
            if (stripos($sql_query, 'SELECT') !== 0) {
                $hasMessageToDisplay = true;
            } else {
                $goto = Url::getFromRoute('/database/sql');

                $sql = new Sql(
                    $this->dbi,
                    $this->relation,
                    new RelationCleanup($this->dbi, $this->relation),
                    new Operations($this->dbi, $this->relation),
                    new Transformations(),
                    $this->template
                );

                $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
                    null, // analyzed_sql_results
                    false, // is_gotofile
                    $_POST['db'], // db
                    null, // table
                    false, // find_real_end
                    null, // sql_query_for_bookmark
                    null, // extra_data
                    null, // message_to_show
                    null, // sql_data
                    $goto, // goto
                    null, // disp_query
                    null, // disp_message
                    $sql_query, // sql_query
                    null // complete_query
                ));
            }
        }

        $sub_part = '_qbe';

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $urlParams['goto'] = Url::getFromRoute('/database/qbe');

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,,,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part);

        $databaseQbe = new Qbe($this->relation, $this->template, $this->dbi, $db, $savedSearchList, $savedSearch);

        $this->render('database/qbe/index', [
            'url_params' => $urlParams,
            'has_message_to_display' => $hasMessageToDisplay,
            'selection_form_html' => $databaseQbe->getSelectionForm(),
        ]);
    }
}
