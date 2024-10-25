<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Message;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Utils\SessionCache;

use function __;

/**
 * The navigation panel
 *
 * Displays server, database and table selection tree.
 */
class NavigationController extends AbstractController
{
    /** @var Navigation */
    private $navigation;

    /** @var Relation */
    private $relation;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Navigation $navigation,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->navigation = $navigation;
        $this->relation = $relation;
    }

    public function __invoke(): void
    {
        if (! $this->response->isAjax()) {
            $this->response->addHTML(
                Message::error(
                    __('Fatal error: The navigation can only be accessed via AJAX')
                )->getDisplay()
            );

            return;
        }

        if (isset($_POST['getNaviSettings']) && $_POST['getNaviSettings']) {
            $pageSettings = new PageSettings('Navi', 'pma_navigation_settings');
            $this->response->addHTML($pageSettings->getErrorHTML());
            $this->response->addJSON('message', $pageSettings->getHTML());

            return;
        }

        if (isset($_POST['reload'])) {
            SessionCache::set('dbs_to_test', false);// Empty database list cache, see #14252
        }

        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            if (isset($_POST['hideNavItem'])) {
                if (! empty($_POST['itemName']) && ! empty($_POST['itemType']) && ! empty($_POST['dbName'])) {
                    $this->navigation->hideNavigationItem(
                        $_POST['itemName'],
                        $_POST['itemType'],
                        $_POST['dbName'],
                        (! empty($_POST['tableName']) ? $_POST['tableName'] : null)
                    );
                }

                return;
            }

            if (isset($_POST['unhideNavItem'])) {
                if (! empty($_POST['itemName']) && ! empty($_POST['itemType']) && ! empty($_POST['dbName'])) {
                    $this->navigation->unhideNavigationItem(
                        $_POST['itemName'],
                        $_POST['itemType'],
                        $_POST['dbName'],
                        (! empty($_POST['tableName']) ? $_POST['tableName'] : null)
                    );
                }

                return;
            }

            if (isset($_POST['showUnhideDialog'])) {
                if (! empty($_POST['dbName'])) {
                    $this->response->addJSON(
                        'message',
                        $this->navigation->getItemUnhideDialog($_POST['dbName'])
                    );
                }

                return;
            }
        }

        $this->response->addJSON('message', $this->navigation->getDisplay());
    }
}
