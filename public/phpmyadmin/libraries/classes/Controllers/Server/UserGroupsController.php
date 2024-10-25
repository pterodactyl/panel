<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\UserGroups;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

/**
 * Displays the 'User groups' sub page under 'Users' page.
 */
class UserGroupsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return;
        }

        $this->addScriptFiles(['server/user_groups.js']);

        /**
         * Only allowed to superuser
         */
        if (! $this->dbi->isSuperUser()) {
            $this->response->addHTML(
                Message::error(__('No Privileges'))->getDisplay()
            );

            return;
        }

        $this->response->addHTML('<div class="container-fluid">');
        $this->render('server/privileges/subnav', [
            'active' => 'user-groups',
            'is_super_user' => $this->dbi->isSuperUser(),
        ]);

        /**
         * Delete user group
         */
        if (! empty($_POST['deleteUserGroup'])) {
            UserGroups::delete($configurableMenusFeature, $_POST['userGroup']);
        }

        /**
         * Add a new user group
         */
        if (! empty($_POST['addUserGroupSubmit'])) {
            UserGroups::edit($configurableMenusFeature, $_POST['userGroup'], true);
        }

        /**
         * Update a user group
         */
        if (! empty($_POST['editUserGroupSubmit'])) {
            UserGroups::edit($configurableMenusFeature, $_POST['userGroup']);
        }

        if (isset($_POST['viewUsers'])) {
            // Display users belonging to a user group
            $this->response->addHTML(UserGroups::getHtmlForListingUsersofAGroup(
                $configurableMenusFeature,
                $_POST['userGroup']
            ));
        }

        if (isset($_GET['addUserGroup'])) {
            // Display add user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup($configurableMenusFeature));
        } elseif (isset($_POST['editUserGroup'])) {
            // Display edit user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup(
                $configurableMenusFeature,
                $_POST['userGroup']
            ));
        } else {
            // Display user groups table
            $this->response->addHTML(UserGroups::getHtmlForUserGroupsTable($configurableMenusFeature));
        }

        $this->response->addHTML('</div>');
    }
}
