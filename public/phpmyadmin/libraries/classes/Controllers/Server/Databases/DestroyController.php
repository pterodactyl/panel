<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function count;
use function is_array;

final class DestroyController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        Transformations $transformations,
        RelationCleanup $relationCleanup
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
    }

    public function __invoke(): void
    {
        global $selected, $errorUrl, $cfg, $dblist, $reload;

        $selected_dbs = $_POST['selected_dbs'] ?? null;

        if (
            ! $this->response->isAjax()
            || (! $this->dbi->isSuperUser() && ! $cfg['AllowUserDropDatabase'])
        ) {
            $message = Message::error();
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        if (
            ! is_array($selected_dbs)
            || $selected_dbs === []
        ) {
            $message = Message::error(__('No databases selected.'));
            $json = ['message' => $message];
            $this->response->setRequestStatus($message->isSuccess());
            $this->response->addJSON($json);

            return;
        }

        $errorUrl = Url::getFromRoute('/server/databases');
        $selected = $selected_dbs;
        $numberOfDatabases = count($selected_dbs);

        foreach ($selected_dbs as $database) {
            $this->relationCleanup->database($database);
            $aQuery = 'DROP DATABASE ' . Util::backquote($database);
            $reload = true;

            $this->dbi->query($aQuery);
            $this->transformations->clear($database);
        }

        $dblist->databases->build();

        $message = Message::success(
            _ngettext(
                '%1$d database has been dropped successfully.',
                '%1$d databases have been dropped successfully.',
                $numberOfDatabases
            )
        );
        $message->addParam($numberOfDatabases);
        $json = ['message' => $message];
        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON($json);
    }
}
