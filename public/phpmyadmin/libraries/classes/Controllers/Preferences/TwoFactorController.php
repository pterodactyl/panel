<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;

use function __;
use function count;

class TwoFactorController extends AbstractController
{
    /** @var Relation */
    private $relation;

    public function __construct(ResponseRenderer $response, Template $template, Relation $relation)
    {
        parent::__construct($response, $template);
        $this->relation = $relation;
    }

    public function __invoke(): void
    {
        global $cfg, $route;

        $relationParameters = $this->relation->getRelationParameters();

        echo $this->template->render('preferences/header', [
            'route' => $route,
            'is_saved' => ! empty($_GET['saved']),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        $twoFactor = new TwoFactor($cfg['Server']['user']);

        if (isset($_POST['2fa_remove'])) {
            if (! $twoFactor->check(true)) {
                echo $this->template->render('preferences/two_factor/confirm', [
                    'form' => $twoFactor->render(),
                ]);

                return;
            }

            $twoFactor->configure('');
            echo Message::rawNotice(__('Two-factor authentication has been removed.'))->getDisplay();
        } elseif (isset($_POST['2fa_configure'])) {
            if (! $twoFactor->configure($_POST['2fa_configure'])) {
                echo $this->template->render('preferences/two_factor/configure', [
                    'form' => $twoFactor->setup(),
                    'configure' => $_POST['2fa_configure'],
                ]);

                return;
            }

            echo Message::rawNotice(__('Two-factor authentication has been configured.'))->getDisplay();
        }

        $backend = $twoFactor->getBackend();
        echo $this->template->render('preferences/two_factor/main', [
            'enabled' => $twoFactor->isWritable(),
            'num_backends' => count($twoFactor->getAvailable()),
            'backend_id' => $backend::$id,
            'backend_name' => $backend::getName(),
            'backend_description' => $backend::getDescription(),
            'backends' => $twoFactor->getAllBackends(),
            'missing' => $twoFactor->getMissingDeps(),
        ]);
    }
}
