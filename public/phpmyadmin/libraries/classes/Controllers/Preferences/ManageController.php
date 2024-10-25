<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\File;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function define;
use function file_exists;
use function is_array;
use function is_string;
use function is_uploaded_file;
use function json_decode;
use function json_encode;
use function mb_strpos;
use function mb_substr;
use function parse_url;
use function str_replace;
use function urlencode;
use function var_export;

use const JSON_PRETTY_PRINT;
use const PHP_URL_PATH;
use const UPLOAD_ERR_OK;

/**
 * User preferences management page.
 */
class ManageController extends AbstractController
{
    /** @var UserPreferences */
    private $userPreferences;

    /** @var Relation */
    private $relation;

    /** @var Config */
    private $config;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        UserPreferences $userPreferences,
        Relation $relation,
        Config $config
    ) {
        parent::__construct($response, $template);
        $this->userPreferences = $userPreferences;
        $this->relation = $relation;
        $this->config = $config;
    }

    public function __invoke(): void
    {
        global $cf, $error, $filename, $json, $lang;
        global $new_config, $return_url, $form_display, $all_ok, $params, $query, $route;

        $cf = new ConfigFile($this->config->baseSettings);
        $this->userPreferences->pageInit($cf);

        $error = '';
        if (isset($_POST['submit_export'], $_POST['export_type']) && $_POST['export_type'] === 'text_file') {
            // export to JSON file
            $this->response->disable();
            $filename = 'phpMyAdmin-config-' . urlencode(Core::getenv('HTTP_HOST')) . '.json';
            Core::downloadHeader($filename, 'application/json');
            $settings = $this->userPreferences->load();
            echo json_encode($settings['config_data'], JSON_PRETTY_PRINT);

            return;
        }

        if (isset($_POST['submit_export'], $_POST['export_type']) && $_POST['export_type'] === 'php_file') {
            // export to JSON file
            $this->response->disable();
            $filename = 'phpMyAdmin-config-' . urlencode(Core::getenv('HTTP_HOST')) . '.php';
            Core::downloadHeader($filename, 'application/php');
            $settings = $this->userPreferences->load();
            echo '/* ' . __('phpMyAdmin configuration snippet') . " */\n\n";
            echo '/* ' . __('Paste it to your config.inc.php') . " */\n\n";
            foreach ($settings['config_data'] as $key => $val) {
                echo '$cfg[\'' . str_replace('/', '\'][\'', $key) . '\'] = ';
                echo var_export($val, true) . ";\n";
            }

            return;
        }

        if (isset($_POST['submit_get_json'])) {
            $settings = $this->userPreferences->load();
            $this->response->addJSON('prefs', json_encode($settings['config_data']));
            $this->response->addJSON('mtime', $settings['mtime']);

            return;
        }

        if (isset($_POST['submit_import'])) {
            // load from JSON file
            $json = '';
            if (
                isset($_POST['import_type'], $_FILES['import_file'])
                && $_POST['import_type'] === 'text_file'
                && is_array($_FILES['import_file'])
                && $_FILES['import_file']['error'] == UPLOAD_ERR_OK
                && isset($_FILES['import_file']['tmp_name'])
                && is_string($_FILES['import_file']['tmp_name'])
                && is_uploaded_file($_FILES['import_file']['tmp_name'])
            ) {
                $importHandle = new File($_FILES['import_file']['tmp_name']);
                $importHandle->checkUploadedFile();
                if ($importHandle->isError()) {
                    $error = $importHandle->getError();
                } else {
                    // read JSON from uploaded file
                    $json = $importHandle->getRawContent();
                }
            } else {
                // read from POST value (json)
                $json = $_POST['json'] ?? null;
            }

            // hide header message
            $_SESSION['userprefs_autoload'] = true;

            $configuration = json_decode($json, true);
            $return_url = $_POST['return_url'] ?? null;
            if (! is_array($configuration)) {
                if (! isset($error)) {
                    $error = __('Could not import configuration');
                }
            } else {
                // sanitize input values: treat them as though
                // they came from HTTP POST request
                $form_display = new UserFormList($cf);
                $new_config = $cf->getFlatDefaultConfig();
                if (! empty($_POST['import_merge'])) {
                    $new_config = array_merge($new_config, $cf->getConfigArray());
                }

                $new_config = array_merge($new_config, $configuration);
                $_POST_bak = $_POST;
                foreach ($new_config as $k => $v) {
                    $_POST[str_replace('/', '-', (string) $k)] = $v;
                }

                $cf->resetConfigData();
                $all_ok = $form_display->process(true, false);
                $all_ok = $all_ok && ! $form_display->hasErrors();
                $_POST = $_POST_bak;

                if (! $all_ok && isset($_POST['fix_errors'])) {
                    $form_display->fixErrors();
                    $all_ok = true;
                }

                if (! $all_ok) {
                    // mimic original form and post json in a hidden field
                    $relationParameters = $this->relation->getRelationParameters();

                    echo $this->template->render('preferences/header', [
                        'route' => $route,
                        'is_saved' => ! empty($_GET['saved']),
                        'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
                    ]);

                    echo $this->template->render('preferences/manage/error', [
                        'form_errors' => $form_display->displayErrors(),
                        'json' => $json,
                        'import_merge' => $_POST['import_merge'] ?? null,
                        'return_url' => $return_url,
                    ]);

                    return;
                }

                // check for ThemeDefault
                $params = [];
                $tmanager = ThemeManager::getInstance();
                if (
                    isset($configuration['ThemeDefault'])
                    && $tmanager->theme->getId() != $configuration['ThemeDefault']
                    && $tmanager->checkTheme($configuration['ThemeDefault'])
                ) {
                    $tmanager->setActiveTheme($configuration['ThemeDefault']);
                    $tmanager->setThemeCookie();
                }

                if (isset($configuration['lang']) && $configuration['lang'] != $lang) {
                    $params['lang'] = $configuration['lang'];
                }

                // save settings
                $result = $this->userPreferences->save($cf->getConfigArray());
                if ($result === true) {
                    if ($return_url) {
                        $query = Util::splitURLQuery($return_url);
                        $return_url = parse_url($return_url, PHP_URL_PATH);

                        foreach ($query as $q) {
                            $pos = mb_strpos($q, '=');
                            $k = mb_substr($q, 0, (int) $pos);
                            if ($k === 'token') {
                                continue;
                            }

                            $params[$k] = mb_substr($q, $pos + 1);
                        }
                    } else {
                        $return_url = 'index.php?route=/preferences/manage';
                    }

                    // reload config
                    $this->config->loadUserPreferences();
                    $this->userPreferences->redirect($return_url ?? '', $params);

                    return;
                }

                $error = $result;
            }
        } elseif (isset($_POST['submit_clear'])) {
            $result = $this->userPreferences->save([]);
            if ($result === true) {
                $params = [];
                $this->config->removeCookie('pma_collaction_connection');
                $this->config->removeCookie('pma_lang');
                $this->userPreferences->redirect('index.php?route=/preferences/manage', $params);

                return;
            } else {
                $error = $result;
            }

            return;
        }

        $this->addScriptFiles(['config.js']);

        $relationParameters = $this->relation->getRelationParameters();

        echo $this->template->render('preferences/header', [
            'route' => $route,
            'is_saved' => ! empty($_GET['saved']),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        if ($error) {
            if (! $error instanceof Message) {
                $error = Message::error($error);
            }

            $error->getDisplay();
        }

        echo $this->template->render('preferences/manage/main', [
            'error' => $error,
            'max_upload_size' => $GLOBALS['config']->get('max_upload_size'),
            'exists_setup_and_not_exists_config' => @file_exists(ROOT_PATH . 'setup/index.php')
                && ! @file_exists(CONFIG_FILE),
        ]);

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }
    }
}
