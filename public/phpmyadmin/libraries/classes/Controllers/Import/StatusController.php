<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;

use function __;
use function header;
use function ini_get;
use function session_start;
use function session_write_close;
use function time;
use function usleep;

/**
 * Import progress bar backend
 */
class StatusController
{
    /** @var Template */
    private $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function __invoke(): void
    {
        global $SESSION_KEY, $upload_id, $plugins, $timestamp;

        [
            $SESSION_KEY,
            $upload_id,
            $plugins,
        ] = Ajax::uploadProgressSetup();

        // $_GET["message"] is used for asking for an import message
        if (isset($_GET['message']) && $_GET['message']) {
            // AJAX requests can't be cached!
            Core::noCacheHeader();

            header('Content-type: text/html');

            // wait 0.3 sec before we check for $_SESSION variable
            usleep(300000);

            $maximumTime = ini_get('max_execution_time');
            $timestamp = time();
            // wait until message is available
            while (($_SESSION['Import_message']['message'] ?? null) == null) {
                // close session before sleeping
                session_write_close();
                // sleep
                usleep(250000); // 0.25 sec
                // reopen session
                session_start();

                if (time() - $timestamp > $maximumTime) {
                    $_SESSION['Import_message']['message'] = Message::error(
                        __('Could not load the progress of the import.')
                    )->getDisplay();
                    break;
                }
            }

            echo $_SESSION['Import_message']['message'] ?? '';

            if (isset($_SESSION['Import_message']['go_back_url'])) {
                echo $this->template->render('import_status', [
                    'go_back_url' => $_SESSION['Import_message']['go_back_url'],
                ]);
            }
        } else {
            Ajax::status($_GET['id']);
        }
    }
}
