<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

final class Indexes
{
    /** @var ResponseRenderer */
    protected $response;

    /** @var Template */
    protected $template;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        $this->response = $response;
        $this->template = $template;
        $this->dbi = $dbi;
    }

    /**
     * Process the data from the edit/create index form,
     * run the query to build the new index
     * and moves back to /table/sql
     *
     * @param Index $index      An Index instance.
     * @param bool  $renameMode Rename the Index mode
     */
    public function doSaveData(Index $index, bool $renameMode, string $db, string $table): void
    {
        global $containerBuilder;

        $error = false;
        if ($renameMode && Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
            $oldIndexName = $_POST['old_index'];

            if ($oldIndexName === 'PRIMARY') {
                if ($index->getName() === '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!')
                    );
                }
            }

            $sql_query = QueryGenerator::getSqlQueryForIndexRename(
                $db,
                $table,
                $oldIndexName,
                $index->getName()
            );
        } else {
            $sql_query = $this->dbi->getTable($db, $table)
                ->getSqlQueryForIndexCreateOrEdit($index, $error);
        }

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sql_query])
            );
        } elseif (! $error) {
            $this->dbi->query($sql_query);
            $response = ResponseRenderer::getInstance();
            if ($response->isAjax()) {
                $message = Message::success(
                    __('Table %1$s has been altered successfully.')
                );
                $message->addParam($table);
                $this->response->addJSON(
                    'message',
                    Generator::getMessage($message, $sql_query, 'success')
                );

                $indexes = Index::getFromTable($table, $db);
                $indexesDuplicates = Index::findDuplicates($table, $db);

                $this->response->addJSON(
                    'index_table',
                    $this->template->render('indexes', [
                        'url_params' => [
                            'db' => $db,
                            'table' => $table,
                        ],
                        'indexes' => $indexes,
                        'indexes_duplicates' => $indexesDuplicates,
                    ])
                );
            } else {
                /** @var StructureController $controller */
                $controller = $containerBuilder->get(StructureController::class);
                $controller();
            }
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);
        }
    }
}
