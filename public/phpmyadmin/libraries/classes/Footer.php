<?php
/**
 * Used to render the footer of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use Traversable;

use function basename;
use function in_array;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function json_last_error;
use function strlen;

/**
 * Class used to output the footer
 */
class Footer
{
    /**
     * Scripts instance
     *
     * @var Scripts
     */
    private $scripts;
    /**
     * Whether we are servicing an ajax request.
     *
     * @var bool
     */
    private $isAjax = false;
    /**
     * Whether to only close the BODY and HTML tags
     * or also include scripts, errors and links
     *
     * @var bool
     */
    private $isMinimal;
    /**
     * Whether to display anything
     *
     * @var bool
     */
    private $isEnabled;

    /** @var Relation */
    private $relation;

    /** @var Template */
    private $template;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        global $dbi;

        $this->template = new Template();
        $this->isEnabled = true;
        $this->scripts = new Scripts();
        $this->isMinimal = false;
        $this->relation = new Relation($dbi);
    }

    /**
     * Remove recursions and iterator objects from an object
     *
     * @param mixed $object Object to clean
     * @param array $stack  Stack used to keep track of recursion, need not be passed for the first time
     *
     * @return mixed Reference passed object
     */
    private static function removeRecursion(&$object, array $stack = [])
    {
        if ((is_object($object) || is_array($object)) && $object) {
            if ($object instanceof Traversable) {
                $object = '***ITERATOR***';
            } elseif (! in_array($object, $stack, true)) {
                $stack[] = $object;
                // @phpstan-ignore-next-line
                foreach ($object as &$subObject) {
                    self::removeRecursion($subObject, $stack);
                }
            } else {
                $object = '***RECURSION***';
            }
        }

        return $object;
    }

    /**
     * Renders the debug messages
     */
    public function getDebugMessage(): string
    {
        $retval = '\'null\'';
        if ($GLOBALS['cfg']['DBG']['sql'] && empty($_REQUEST['no_debug']) && ! empty($_SESSION['debug'])) {
            // Remove recursions and iterators from $_SESSION['debug']
            self::removeRecursion($_SESSION['debug']);

            $retval = (string) json_encode($_SESSION['debug']);
            $_SESSION['debug'] = [];

            return json_last_error() ? '\'false\'' : $retval;
        }

        $_SESSION['debug'] = [];

        return $retval;
    }

    /**
     * Returns the url of the current page
     */
    public function getSelfUrl(): string
    {
        global $route, $db, $table, $server;

        $params = [];
        if (isset($route)) {
            $params['route'] = $route;
        }

        if (isset($db) && strlen($db) > 0) {
            $params['db'] = $db;
        }

        if (isset($table) && strlen($table) > 0) {
            $params['table'] = $table;
        }

        $params['server'] = $server;

        // needed for server privileges tabs
        if (isset($_GET['viewing_mode']) && in_array($_GET['viewing_mode'], ['server', 'db', 'table'])) {
            $params['viewing_mode'] = $_GET['viewing_mode'];
        }

        /**
         * @todo    coming from /server/privileges, here $db is not set,
         *          add the following condition below when that is fixed
         *          && $_GET['checkprivsdb'] == $db
         */
        if (isset($_GET['checkprivsdb'])) {
            $params['checkprivsdb'] = $_GET['checkprivsdb'];
        }

        /**
         * @todo    coming from /server/privileges, here $table is not set,
         *          add the following condition below when that is fixed
         *          && $_REQUEST['checkprivstable'] == $table
         */
        if (isset($_GET['checkprivstable'])) {
            $params['checkprivstable'] = $_GET['checkprivstable'];
        }

        if (isset($_REQUEST['single_table']) && in_array($_REQUEST['single_table'], [true, false])) {
            $params['single_table'] = $_REQUEST['single_table'];
        }

        return basename(Core::getenv('SCRIPT_NAME')) . Url::getCommonRaw($params);
    }

    /**
     * Renders the link to open a new page
     */
    public function getErrorMessages(): string
    {
        $retval = '';
        if ($GLOBALS['errorHandler']->hasDisplayErrors()) {
            $retval .= $GLOBALS['errorHandler']->getDispErrors();
        }

        /**
         * Report php errors
         */
        $GLOBALS['errorHandler']->reportErrors();

        return $retval;
    }

    /**
     * Saves query in history
     */
    private function setHistory(): void
    {
        global $dbi;

        if (
            (
                isset($_REQUEST['no_history'])
                && is_scalar($_REQUEST['no_history'])
                && strlen((string) $_REQUEST['no_history']) > 0
            )
            || ! empty($GLOBALS['error_message'])
            || empty($GLOBALS['sql_query'])
            || ! isset($dbi)
            || ! $dbi->isConnected()
        ) {
            return;
        }

        $this->relation->setHistory(
            isset($GLOBALS['db']) && is_scalar($GLOBALS['db']) ? (string) $GLOBALS['db'] : '',
            isset($GLOBALS['table']) && is_scalar($GLOBALS['table']) ? (string) $GLOBALS['table'] : '',
            $GLOBALS['cfg']['Server']['user'],
            $GLOBALS['sql_query']
        );
    }

    /**
     * Disables the rendering of the footer
     */
    public function disable(): void
    {
        $this->isEnabled = false;
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Turn on minimal display mode
     */
    public function setMinimal(): void
    {
        $this->isMinimal = true;
    }

    /**
     * Returns the Scripts object
     *
     * @return Scripts object
     */
    public function getScripts(): Scripts
    {
        return $this->scripts;
    }

    /**
     * Renders the footer
     */
    public function getDisplay(): string
    {
        $this->setHistory();
        if ($this->isEnabled) {
            if (! $this->isAjax && ! $this->isMinimal) {
                if (Core::getenv('SCRIPT_NAME')) {
                    $url = $this->getSelfUrl();
                }

                $this->scripts->addCode('var debugSQLInfo = ' . $this->getDebugMessage() . ';');
                $errorMessages = $this->getErrorMessages();
                $scripts = $this->scripts->getDisplay();

                if ($GLOBALS['cfg']['DBG']['demo']) {
                    $git = new Git(true, ROOT_PATH);
                    $gitRevisionInfo = $git->getGitRevisionInfo();
                }

                $footer = Config::renderFooter();
            }

            return $this->template->render('footer', [
                'is_ajax' => $this->isAjax,
                'is_minimal' => $this->isMinimal,
                'self_url' => $url ?? null,
                'error_messages' => $errorMessages ?? '',
                'scripts' => $scripts ?? '',
                'is_demo' => $GLOBALS['cfg']['DBG']['demo'],
                'git_revision_info' => $gitRevisionInfo ?? [],
                'footer' => $footer ?? '',
            ]);
        }

        return '';
    }
}
