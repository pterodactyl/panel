<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;

class LogoutController
{
    public function __invoke(): void
    {
        global $auth_plugin, $token_mismatch;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $token_mismatch) {
            Core::sendHeaderLocation('./index.php?route=/');

            return;
        }

        $auth_plugin->logOut();
    }
}
