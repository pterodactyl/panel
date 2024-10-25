<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Setup\Index;

use function __;
use function array_keys;
use function is_scalar;
use function is_string;

class HomeController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function __invoke(array $params): string
    {
        $formset = isset($params['formset']) && is_string($params['formset']) ? $params['formset'] : '';

        $pages = $this->getPages();

        // message handling
        Index::messagesBegin();

        // Check phpMyAdmin version
        if (isset($params['version_check'])) {
            Index::versionCheck();
        }

        // Perform various security, compatibility and consistency checks
        $configChecker = new ServerConfigChecks($this->config);
        $configChecker->performConfigChecks();

        $text = __(
            'You are not using a secure connection; all data (including potentially '
            . 'sensitive information, like passwords) is transferred unencrypted!'
        );
        $text .= ' <a href="#">';
        $text .= __(
            'If your server is also configured to accept HTTPS requests '
            . 'follow this link to use a secure connection.'
        );
        $text .= '</a>';
        Index::messagesSet('notice', 'no_https', __('Insecure connection'), $text);

        Index::messagesEnd();
        $messages = Index::messagesShowHtml();

        // prepare unfiltered language list
        $sortedLanguages = LanguageManager::getInstance()->sortedLanguages();
        $languages = [];
        foreach ($sortedLanguages as $language) {
            $languages[] = [
                'code' => $language->getCode(),
                'name' => $language->getName(),
                'is_active' => $language->isActive(),
            ];
        }

        $servers = [];
        foreach (array_keys($this->config->getServers()) as $id) {
            $servers[$id] = [
                'id' => $id,
                'name' => $this->config->getServerName($id),
                'auth_type' => $this->config->getValue('Servers/' . $id . '/auth_type'),
                'dsn' => $this->config->getServerDSN($id),
                'params' => [
                    'token' => $_SESSION[' PMA_token '],
                    'edit' => [
                        'page' => 'servers',
                        'mode' => 'edit',
                        'id' => $id,
                    ],
                    'remove' => [
                        'page' => 'servers',
                        'mode' => 'remove',
                        'id' => $id,
                    ],
                ],
            ];
        }

        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        return $this->template->render('setup/home/index', [
            'formset' => $formset,
            'languages' => $languages,
            'messages' => $messages,
            'server_count' => $this->config->getServerCount(),
            'servers' => $servers,
            'pages' => $pages,
            'has_check_page_refresh' => $hasCheckPageRefresh,
            'eol' => isset($_SESSION['eol']) && is_scalar($_SESSION['eol'])
                ? $_SESSION['eol']
                : ($GLOBALS['config']->get('PMA_IS_WINDOWS') ? 'win' : 'unix'),
        ]);
    }
}
