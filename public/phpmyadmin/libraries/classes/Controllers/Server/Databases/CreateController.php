<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_key_exists;
use function explode;
use function mb_strlen;
use function mb_strtolower;
use function str_contains;

final class CreateController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $cfg, $db;

        $params = [
            'new_db' => $_POST['new_db'] ?? null,
            'db_collation' => $_POST['db_collation'] ?? null,
        ];

        if (! isset($params['new_db']) || mb_strlen($params['new_db']) === 0 || ! $this->response->isAjax()) {
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        // lower_case_table_names=1 `DB` becomes `db`
        if ($this->dbi->getLowerCaseNames() === '1') {
            $params['new_db'] = mb_strtolower($params['new_db']);
        }

        /**
         * Builds and executes the db creation sql query
         */
        $sqlQuery = 'CREATE DATABASE ' . Util::backquote($params['new_db']);
        if (! empty($params['db_collation'])) {
            [$databaseCharset] = explode('_', $params['db_collation']);
            $charsets = Charsets::getCharsets($this->dbi, $cfg['Server']['DisableIS']);
            $collations = Charsets::getCollations($this->dbi, $cfg['Server']['DisableIS']);
            if (
                array_key_exists($databaseCharset, $charsets)
                && array_key_exists($params['db_collation'], $collations[$databaseCharset])
            ) {
                $sqlQuery .= ' DEFAULT'
                    . Util::getCharsetQueryPart($params['db_collation']);
            }
        }

        $sqlQuery .= ';';

        $result = $this->dbi->tryQuery($sqlQuery);

        if (! $result) {
            // avoid displaying the not-created db name in header or navi panel
            $db = '';

            $message = Message::rawError($this->dbi->getError());
            $json = ['message' => $message];

            $this->response->setRequestStatus(false);
        } else {
            $db = $params['new_db'];

            $message = Message::success(__('Database %1$s has been created.'));
            $message->addParam($params['new_db']);

            $scriptName = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');

            $json = [
                'message' => $message,
                'sql_query' => Generator::getMessage('', $sqlQuery, 'success'),
                'url' => $scriptName . Url::getCommon(
                    ['db' => $params['new_db']],
                    ! str_contains($scriptName, '?') ? '?' : '&'
                ),
            ];
        }

        $this->response->addJSON($json);
    }
}
