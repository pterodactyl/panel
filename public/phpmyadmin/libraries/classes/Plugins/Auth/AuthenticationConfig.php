<?php
/**
 * Config Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Util;

use function __;
use function count;
use function defined;
use function sprintf;
use function trigger_error;

use const E_USER_NOTICE;
use const E_USER_WARNING;

/**
 * Handles the config authentication method
 */
class AuthenticationConfig extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     *
     * @return bool always true
     */
    public function showLoginForm(): bool
    {
        $response = ResponseRenderer::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            // reload_flag removes the token parameter from the URL and reloads
            $response->addJSON('reload_flag', '1');
            if (defined('TESTSUITE')) {
                return true;
            }

            exit;
        }

        return true;
    }

    /**
     * Gets authentication credentials
     *
     * @return bool always true
     */
    public function readCredentials(): bool
    {
        if ($GLOBALS['token_provided'] && $GLOBALS['token_mismatch']) {
            return false;
        }

        $this->user = $GLOBALS['cfg']['Server']['user'];
        $this->password = $GLOBALS['cfg']['Server']['password'];

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure($failure): void
    {
        global $dbi;

        parent::showFailure($failure);
        $conn_error = $dbi->getError();
        if (! $conn_error) {
            $conn_error = __('Cannot connect: invalid settings.');
        }

        /* HTML header */
        $response = ResponseRenderer::getInstance();
        $response->getFooter()
            ->setMinimal();
        $header = $response->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle(__('Access denied!'));
        $header->disableMenuAndConsole();
        echo '<br><br>
    <div class="text-center">
        <h1>';
        echo sprintf(__('Welcome to %s'), ' phpMyAdmin ');
        echo '</h1>
    </div>
    <br>
    <table class="table table-borderless text-start w-75 mx-auto">
        <tr>
            <td>';
        if (isset($GLOBALS['allowDeny_forbidden']) && $GLOBALS['allowDeny_forbidden']) {
            trigger_error(__('Access denied!'), E_USER_NOTICE);
        } else {
            // Check whether user has configured something
            if ($GLOBALS['config']->sourceMtime == 0) {
                echo '<p>' , sprintf(
                    __(
                        'You probably did not create a configuration file.'
                        . ' You might want to use the %1$ssetup script%2$s to'
                        . ' create one.'
                    ),
                    '<a href="setup/">',
                    '</a>'
                ) , '</p>' , "\n";
            } elseif (
                ! isset($GLOBALS['errno'])
                || (isset($GLOBALS['errno']) && $GLOBALS['errno'] != 2002)
                && $GLOBALS['errno'] != 2003
            ) {
                // if we display the "Server not responding" error, do not confuse
                // users by telling them they have a settings problem
                // (note: it's true that they could have a badly typed host name,
                // but anyway the current message tells that the server
                //  rejected the connection, which is not really what happened)
                // 2002 is the error given by mysqli
                // 2003 is the error given by mysql
                trigger_error(
                    __(
                        'phpMyAdmin tried to connect to the MySQL server, and the'
                        . ' server rejected the connection. You should check the'
                        . ' host, username and password in your configuration and'
                        . ' make sure that they correspond to the information given'
                        . ' by the administrator of the MySQL server.'
                    ),
                    E_USER_WARNING
                );
            }

            echo Generator::mysqlDie($conn_error, '', true, '', false);
        }

        $GLOBALS['errorHandler']->dispUserErrors();
        echo '</td>
        </tr>
        <tr>
            <td>' , "\n";
        echo '<a href="'
            , Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabServer'], 'server')
            , '" class="btn btn-primary mt-1 mb-1 disableAjax">'
            , __('Retry to connect')
            , '</a>' , "\n";
        echo '</td>
        </tr>' , "\n";
        if (count($GLOBALS['cfg']['Servers']) > 1) {
            // offer a chance to login to other servers if the current one failed
            echo '<tr>' , "\n";
            echo ' <td>' , "\n";
            echo Select::render(true, true);
            echo ' </td>' , "\n";
            echo '</tr>' , "\n";
        }

        echo '</table>' , "\n";
        if (! defined('TESTSUITE')) {
            exit;
        }
    }
}
