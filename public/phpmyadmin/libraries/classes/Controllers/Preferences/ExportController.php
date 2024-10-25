<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\ExportForm;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

use function define;
use function ltrim;

class ExportController extends AbstractController
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
        global $cfg, $cf, $error, $tabHash, $hash, $server, $route;

        $cf = new ConfigFile($this->config->baseSettings);
        $this->userPreferences->pageInit($cf);

        $formDisplay = new ExportForm($cf, 1);

        if (isset($_POST['revert'])) {
            // revert erroneous fields to their default values
            $formDisplay->fixErrors();
            $this->redirect('/preferences/export');

            return;
        }

        $error = null;
        if ($formDisplay->process(false) && ! $formDisplay->hasErrors()) {
            // Load 2FA settings
            $twoFactor = new TwoFactor($cfg['Server']['user']);
            // save settings
            $result = $this->userPreferences->save($cf->getConfigArray());
            // save back the 2FA setting only
            $twoFactor->save();
            if ($result === true) {
                // reload config
                $this->config->loadUserPreferences();
                $tabHash = $_POST['tab_hash'] ?? null;
                $hash = ltrim($tabHash, '#');
                $this->userPreferences->redirect('index.php?route=/preferences/export', null, $hash);

                return;
            }

            $error = $result;
        }

        $this->addScriptFiles(['config.js']);

        $relationParameters = $this->relation->getRelationParameters();

        $this->render('preferences/header', [
            'route' => $route,
            'is_saved' => ! empty($_GET['saved']),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        if ($formDisplay->hasErrors()) {
            $formErrors = $formDisplay->displayErrors();
        }

        $this->render('preferences/forms/main', [
            'error' => $error ? $error->getDisplay() : '',
            'has_errors' => $formDisplay->hasErrors(),
            'errors' => $formErrors ?? null,
            'form' => $formDisplay->getDisplay(
                true,
                Url::getFromRoute('/preferences/export'),
                ['server' => $server]
            ),
        ]);

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }
    }
}
