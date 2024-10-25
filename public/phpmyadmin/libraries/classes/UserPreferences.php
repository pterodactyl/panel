<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseName;

use function __;
use function array_flip;
use function array_merge;
use function basename;
use function htmlspecialchars;
use function http_build_query;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function str_contains;
use function time;
use function urlencode;

/**
 * Functions for displaying user preferences pages
 */
class UserPreferences
{
    /** @var Relation */
    private $relation;

    /** @var Template */
    public $template;

    public function __construct()
    {
        global $dbi;

        $this->relation = new Relation($dbi);
        $this->template = new Template();
    }

    /**
     * Common initialization for user preferences modification pages
     *
     * @param ConfigFile $cf Config file instance
     */
    public function pageInit(ConfigFile $cf): void
    {
        $forms_all_keys = UserFormList::getFields();
        $cf->resetConfigData(); // start with a clean instance
        $cf->setAllowedKeys($forms_all_keys);
        $cf->setCfgUpdateReadMapping(
            [
                'Server/hide_db' => 'Servers/1/hide_db',
                'Server/only_db' => 'Servers/1/only_db',
            ]
        );
        $cf->updateWithGlobalConfig($GLOBALS['cfg']);
    }

    /**
     * Loads user preferences
     *
     * Returns an array:
     * * config_data - path => value pairs
     * * mtime - last modification time
     * * type - 'db' (config read from pmadb) or 'session' (read from user session)
     *
     * @psalm-return array{config_data: mixed[], mtime: int, type: 'session'|'db'}
     */
    public function load(): array
    {
        global $dbi;

        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->userPreferencesFeature === null) {
            // no pmadb table, use session storage
            if (! isset($_SESSION['userconfig']) || ! is_array($_SESSION['userconfig'])) {
                $_SESSION['userconfig'] = ['db' => [], 'ts' => time()];
            }

            $configData = $_SESSION['userconfig']['db'] ?? null;
            $timestamp = $_SESSION['userconfig']['ts'] ?? null;

            return [
                'config_data' => is_array($configData) ? $configData : [],
                'mtime' => is_int($timestamp) ? $timestamp : time(),
                'type' => 'session',
            ];
        }

        // load configuration from pmadb
        $query_table = Util::backquote($relationParameters->userPreferencesFeature->database) . '.'
            . Util::backquote($relationParameters->userPreferencesFeature->userConfig);
        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts'
            . ' FROM ' . $query_table
            . ' WHERE `username` = \''
            . $dbi->escapeString((string) $relationParameters->user)
            . '\'';
        $row = $dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, DatabaseInterface::CONNECT_CONTROL);
        if (! is_array($row) || ! isset($row['config_data']) || ! isset($row['ts'])) {
            return ['config_data' => [], 'mtime' => time(), 'type' => 'db'];
        }

        $configData = is_string($row['config_data']) ? json_decode($row['config_data'], true) : [];

        return [
            'config_data' => is_array($configData) ? $configData : [],
            'mtime' => is_numeric($row['ts']) ? (int) $row['ts'] : time(),
            'type' => 'db',
        ];
    }

    /**
     * Saves user preferences
     *
     * @param array $config_array configuration array
     *
     * @return true|Message
     */
    public function save(array $config_array)
    {
        global $dbi;

        $relationParameters = $this->relation->getRelationParameters();
        $server = $GLOBALS['server'] ?? $GLOBALS['cfg']['ServerDefault'];
        $cache_key = 'server_' . $server;
        if (
            $relationParameters->userPreferencesFeature === null
            || $relationParameters->user === null
            || $relationParameters->db === null
        ) {
            // no pmadb table, use session storage
            $_SESSION['userconfig'] = [
                'db' => $config_array,
                'ts' => time(),
            ];
            if (isset($_SESSION['cache'][$cache_key]['userprefs'])) {
                unset($_SESSION['cache'][$cache_key]['userprefs']);
            }

            return true;
        }

        // save configuration to pmadb
        $query_table = Util::backquote($relationParameters->userPreferencesFeature->database) . '.'
            . Util::backquote($relationParameters->userPreferencesFeature->userConfig);
        $query = 'SELECT `username` FROM ' . $query_table
            . ' WHERE `username` = \''
            . $dbi->escapeString($relationParameters->user)
            . '\'';

        $has_config = $dbi->fetchValue($query, 0, DatabaseInterface::CONNECT_CONTROL);
        $config_data = json_encode($config_array);
        if ($has_config) {
            $query = 'UPDATE ' . $query_table
                . ' SET `timevalue` = NOW(), `config_data` = \''
                . $dbi->escapeString($config_data)
                . '\''
                . ' WHERE `username` = \''
                . $dbi->escapeString($relationParameters->user)
                . '\'';
        } else {
            $query = 'INSERT INTO ' . $query_table
                . ' (`username`, `timevalue`,`config_data`) '
                . 'VALUES (\''
                . $dbi->escapeString($relationParameters->user) . '\', NOW(), '
                . '\'' . $dbi->escapeString($config_data) . '\')';
        }

        if (isset($_SESSION['cache'][$cache_key]['userprefs'])) {
            unset($_SESSION['cache'][$cache_key]['userprefs']);
        }

        if (! $dbi->tryQuery($query, DatabaseInterface::CONNECT_CONTROL)) {
            $message = Message::error(__('Could not save configuration'));
            $message->addMessage(Message::error($dbi->getError(DatabaseInterface::CONNECT_CONTROL)), '<br><br>');
            if (! $this->hasAccessToDatabase($relationParameters->db)) {
                /**
                 * When phpMyAdmin cached the configuration storage parameters, it checked if the database can be
                 * accessed, so if it could not be accessed anymore, then the cache must be cleared as it's out of date.
                 *
                 * @psalm-suppress MixedArrayAssignment
                 */
                $_SESSION['relation'][$GLOBALS['server']] = [];
                $message->addMessage(Message::error(htmlspecialchars(
                    __('The phpMyAdmin configuration storage database could not be accessed.')
                )), '<br><br>');
            }

            return $message;
        }

        return true;
    }

    private function hasAccessToDatabase(DatabaseName $database): bool
    {
        $escapedDb = $GLOBALS['dbi']->escapeString($database->getName());
        $query = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \'' . $escapedDb . '\';';
        if ($GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SHOW DATABASES LIKE \'' . Util::escapeMysqlWildcards($escapedDb) . '\';';
        }

        return (bool) $GLOBALS['dbi']->fetchSingleRow($query, 'ASSOC', DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * Returns a user preferences array filtered by $cfg['UserprefsDisallow']
     * (exclude list) and keys from user preferences form (allow list)
     *
     * @param array $config_data path => value pairs
     *
     * @return array
     */
    public function apply(array $config_data)
    {
        $cfg = [];
        $excludeList = array_flip($GLOBALS['cfg']['UserprefsDisallow']);
        $allowList = array_flip(UserFormList::getFields());
        // allow some additional fields which are custom handled
        $allowList['ThemeDefault'] = true;
        $allowList['lang'] = true;
        $allowList['Server/hide_db'] = true;
        $allowList['Server/only_db'] = true;
        $allowList['2fa'] = true;
        foreach ($config_data as $path => $value) {
            if (! isset($allowList[$path]) || isset($excludeList[$path])) {
                continue;
            }

            Core::arrayWrite($path, $cfg, $value);
        }

        return $cfg;
    }

    /**
     * Updates one user preferences option (loads and saves to database).
     *
     * No validation is done!
     *
     * @param string $path          configuration
     * @param mixed  $value         value
     * @param mixed  $default_value default value
     *
     * @return true|Message
     */
    public function persistOption($path, $value, $default_value)
    {
        $prefs = $this->load();
        if ($value === $default_value) {
            if (! isset($prefs['config_data'][$path])) {
                return true;
            }

            unset($prefs['config_data'][$path]);
        } else {
            $prefs['config_data'][$path] = $value;
        }

        return $this->save($prefs['config_data']);
    }

    /**
     * Redirects after saving new user preferences
     *
     * @param string     $file_name Filename
     * @param array|null $params    URL parameters
     * @param string     $hash      Hash value
     */
    public function redirect(
        $file_name,
        $params = null,
        $hash = null
    ): void {
        // redirect
        $url_params = ['saved' => 1];
        if (is_array($params)) {
            $url_params = array_merge($params, $url_params);
        }

        if ($hash) {
            $hash = '#' . urlencode($hash);
        }

        Core::sendHeaderLocation('./' . $file_name
            . Url::getCommonRaw($url_params, ! str_contains($file_name, '?') ? '?' : '&') . $hash);
    }

    /**
     * Shows form which allows to quickly load
     * settings stored in browser's local storage
     *
     * @return string
     */
    public function autoloadGetHeader()
    {
        if (isset($_REQUEST['prefs_autoload']) && $_REQUEST['prefs_autoload'] === 'hide') {
            $_SESSION['userprefs_autoload'] = true;

            return '';
        }

        $script_name = basename(basename($GLOBALS['PMA_PHP_SELF']));
        $return_url = $script_name . '?' . http_build_query($_GET, '', '&');

        return $this->template->render('preferences/autoload', [
            'hidden_inputs' => Url::getHiddenInputs(),
            'return_url' => $return_url,
        ]);
    }
}
