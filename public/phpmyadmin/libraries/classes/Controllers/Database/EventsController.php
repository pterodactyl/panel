<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function strlen;

final class EventsController extends AbstractController
{
    /** @var Events */
    private $events;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        Events $events,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db);
        $this->events = $events;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        global $db, $tables, $num_tables, $total_num_tables, $sub_part, $errors, $text_dir;
        global $tooltip_truename, $tooltip_aliasname, $pos, $cfg, $errorUrl;

        $this->addScriptFiles(['database/events.js']);

        if (! $this->response->isAjax()) {
            Util::checkParameters(['db']);

            $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
            $errorUrl .= Url::getCommon(['db' => $db], '&');

            if (! $this->hasDatabase()) {
                return;
            }

            [
                $tables,
                $num_tables,
                $total_num_tables,
                $sub_part,,,
                $tooltip_truename,
                $tooltip_aliasname,
                $pos,
            ] = Util::getDbInfo($db, $sub_part ?? '');
        } elseif (strlen($db) > 0) {
            $this->dbi->selectDb($db);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $this->events->handleEditor();
        $this->events->export();

        $items = $this->dbi->getEvents($db);

        $this->render('database/events/index', [
            'db' => $db,
            'items' => $items,
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db),
            'scheduler_state' => $this->events->getEventSchedulerStatus(),
            'text_dir' => $text_dir,
            'is_ajax' => $this->response->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);
    }
}
