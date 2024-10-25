<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function htmlspecialchars;

/**
 * Database SQL executor
 */
class SqlController extends AbstractController
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    public function __construct(ResponseRenderer $response, Template $template, string $db, SqlQueryForm $sqlQueryForm)
    {
        parent::__construct($response, $template, $db);
        $this->sqlQueryForm = $sqlQueryForm;
    }

    public function __invoke(): void
    {
        global $goto, $back, $db, $cfg, $errorUrl;

        $this->addScriptFiles(['makegrid.js', 'vendor/jquery/jquery.uitablefilter.js', 'sql.js']);

        $pageSettings = new PageSettings('Sql');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = Url::getFromRoute('/database/sql');
        $back = $goto;

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            $db,
            '',
            true,
            false,
            isset($_POST['delimiter'])
                ? htmlspecialchars($_POST['delimiter'])
                : ';'
        ));
    }
}
