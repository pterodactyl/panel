<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Server\Privileges;

use function __;
use function strlen;

/**
 * Functions for user password
 */
class UserPassword
{
    /** @var Privileges */
    private $serverPrivileges;

    /**
     * @param Privileges $serverPrivileges Privileges object
     */
    public function __construct(Privileges $serverPrivileges)
    {
        $this->serverPrivileges = $serverPrivileges;
    }

    /**
     * Generate the message
     *
     * @return array   error value and message
     */
    public function setChangePasswordMsg()
    {
        $error = false;
        $message = Message::success(__('The profile has been updated.'));

        if ($_POST['nopass'] != '1') {
            if (strlen($_POST['pma_pw']) === 0 || strlen($_POST['pma_pw2']) === 0) {
                $message = Message::error(__('The password is empty!'));
                $error = true;
            } elseif ($_POST['pma_pw'] !== $_POST['pma_pw2']) {
                $message = Message::error(
                    __('The passwords aren\'t the same!')
                );
                $error = true;
            } elseif (strlen($_POST['pma_pw']) > 256) {
                $message = Message::error(__('Password is too long!'));
                $error = true;
            }
        }

        return [
            'error' => $error,
            'msg' => $message,
        ];
    }

    /**
     * Change the password
     *
     * @param string $password New password
     */
    public function changePassword($password): string
    {
        global $auth_plugin, $dbi;

        $hashing_function = $this->changePassHashingFunction();

        [$username, $hostname] = $dbi->getCurrentUserAndHost();

        $serverVersion = $dbi->getVersion();

        if (isset($_POST['authentication_plugin']) && ! empty($_POST['authentication_plugin'])) {
            $orig_auth_plugin = $_POST['authentication_plugin'];
        } else {
            $orig_auth_plugin = $this->serverPrivileges->getCurrentAuthenticationPlugin('change', $username, $hostname);
        }

        $sql_query = 'SET password = '
            . ($password == '' ? '\'\'' : $hashing_function . '(\'***\')');

        $isPerconaOrMySql = Compatibility::isMySqlOrPerconaDb();
        if ($isPerconaOrMySql && $serverVersion >= 50706) {
            $sql_query = 'ALTER USER \'' . $dbi->escapeString($username)
                . '\'@\'' . $dbi->escapeString($hostname)
                . '\' IDENTIFIED WITH ' . $orig_auth_plugin . ' BY '
                . ($password == '' ? '\'\'' : '\'***\'');
        } elseif (
            ($isPerconaOrMySql && $serverVersion >= 50507)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200)
        ) {
            // For MySQL and Percona versions 5.5.7+ and MariaDB versions 5.2+,
            // explicitly set value of `old_passwords` so that
            // it does not give an error while using
            // the PASSWORD() function
            if ($orig_auth_plugin === 'sha256_password') {
                $value = 2;
            } else {
                $value = 0;
            }

            $dbi->tryQuery('SET `old_passwords` = ' . $value . ';');
        }

        $this->changePassUrlParamsAndSubmitQuery(
            $username,
            $hostname,
            $password,
            $sql_query,
            $hashing_function,
            $orig_auth_plugin
        );

        $auth_plugin->handlePasswordChange($password);

        return $sql_query;
    }

    /**
     * Generate the hashing function
     *
     * @return string
     */
    private function changePassHashingFunction()
    {
        if (isset($_POST['authentication_plugin']) && $_POST['authentication_plugin'] === 'mysql_old_password') {
            $hashing_function = 'OLD_PASSWORD';
        } else {
            $hashing_function = 'PASSWORD';
        }

        return $hashing_function;
    }

    /**
     * Changes password for a user
     *
     * @param string $username         Username
     * @param string $hostname         Hostname
     * @param string $password         Password
     * @param string $sql_query        SQL query
     * @param string $hashing_function Hashing function
     * @param string $orig_auth_plugin Original Authentication Plugin
     */
    private function changePassUrlParamsAndSubmitQuery(
        $username,
        $hostname,
        $password,
        $sql_query,
        $hashing_function,
        $orig_auth_plugin
    ): void {
        global $dbi;

        $err_url = Url::getFromRoute('/user-password');

        $serverVersion = $dbi->getVersion();

        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706) {
            $local_query = 'ALTER USER \'' . $dbi->escapeString($username)
                . '\'@\'' . $dbi->escapeString($hostname) . '\''
                . ' IDENTIFIED with ' . $orig_auth_plugin . ' BY '
                . ($password == ''
                ? '\'\''
                : '\'' . $dbi->escapeString($password) . '\'');
        } elseif (
            Compatibility::isMariaDb()
            && $serverVersion >= 50200
            && $serverVersion < 100100
            && $orig_auth_plugin !== ''
        ) {
            if ($orig_auth_plugin === 'mysql_native_password') {
                // Set the hashing method used by PASSWORD()
                // to be 'mysql_native_password' type
                $dbi->tryQuery('SET old_passwords = 0;');
            } elseif ($orig_auth_plugin === 'sha256_password') {
                // Set the hashing method used by PASSWORD()
                // to be 'sha256_password' type
                $dbi->tryQuery('SET `old_passwords` = 2;');
            }

            $hashedPassword = $this->serverPrivileges->getHashedPassword($_POST['pma_pw']);

            $local_query = 'UPDATE `mysql`.`user` SET'
                . " `authentication_string` = '" . $hashedPassword
                . "', `Password` = '', "
                . " `plugin` = '" . $orig_auth_plugin . "'"
                . " WHERE `User` = '" . $dbi->escapeString($username)
                . "' AND Host = '" . $dbi->escapeString($hostname) . "';";
        } else {
            $local_query = 'SET password = ' . ($password == ''
                ? '\'\''
                : $hashing_function . '(\''
                    . $dbi->escapeString($password) . '\')');
        }

        if (! @$dbi->tryQuery($local_query)) {
            Generator::mysqlDie(
                $dbi->getError(),
                $sql_query,
                false,
                $err_url
            );
        }

        // Flush privileges after successful password change
        $dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    public function getFormForChangePassword(?string $username, ?string $hostname): string
    {
        return $this->serverPrivileges->getFormForChangePassword($username ?? '', $hostname ?? '', false);
    }
}
