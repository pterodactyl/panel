<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function is_scalar;
use function preg_match;
use function strlen;

/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */
final class UrlRedirector
{
    /**
     * @psalm-return never
     */
    public static function redirect(): void
    {
        global $containerBuilder, $dbi;

        // Load database service because services.php is not available here
        $dbi = DatabaseInterface::load();
        $containerBuilder->set(DatabaseInterface::class, $dbi);

        // Only output the http headers
        $response = ResponseRenderer::getInstance();
        $response->getHeader()->sendHttpHeaders();
        $response->disable();

        if (
            ! isset($_GET['url']) || ! is_scalar($_GET['url']) || strlen((string) $_GET['url']) === 0
            || ! preg_match('/^https:\/\/[^\n\r]*$/', (string) $_GET['url'])
            || ! Core::isAllowedDomain((string) $_GET['url'])
        ) {
            Core::sendHeaderLocation('./');

            exit;
        }

        /**
         * JavaScript redirection is necessary. Because if header() is used then web browser sometimes does not change
         * the HTTP_REFERER field and so with old URL as Referer, token also goes to external site.
         *
         * @var Template $template
         */
        $template = $containerBuilder->get('template');
        echo $template->render('javascript/redirect', [
            'url' => Sanitize::escapeJsString((string) $_GET['url']),
        ]);
        // Display redirecting msg on screen.
        // Do not display the value of $_GET['url'] to avoid showing injected content
        echo __('Taking you to the target site.');

        exit;
    }
}
