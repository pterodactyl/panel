<?php
/**
 * Server config checks management
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Core;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Setup\Index as SetupIndex;
use PhpMyAdmin\Url;

use function __;
use function function_exists;
use function htmlspecialchars;
use function ini_get;
use function is_string;
use function mb_strlen;
use function sodium_crypto_secretbox_keygen;
use function sprintf;

use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

/**
 * Performs various compatibility, security and consistency checks on current config
 *
 * Outputs results to message list, must be called between SetupIndex::messagesBegin()
 * and SetupIndex::messagesEnd()
 */
class ServerConfigChecks
{
    /** @var ConfigFile configurations being checked */
    protected $cfg;

    /**
     * @param ConfigFile $cfg Configuration
     */
    public function __construct(ConfigFile $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * Perform config checks
     */
    public function performConfigChecks(): void
    {
        $blowfishSecret = $this->cfg->get('blowfish_secret');
        $blowfishSecretSet = false;
        $cookieAuthUsed = false;

        [$cookieAuthUsed, $blowfishSecret, $blowfishSecretSet] = $this->performConfigChecksServers(
            $cookieAuthUsed,
            $blowfishSecret,
            $blowfishSecretSet
        );

        $this->performConfigChecksCookieAuthUsed($cookieAuthUsed, $blowfishSecretSet, $blowfishSecret);

        // $cfg['AllowArbitraryServer']
        // should be disabled
        if ($this->cfg->getValue('AllowArbitraryServer')) {
            $sAllowArbitraryServerWarn = sprintf(
                __(
                    'This %soption%s should be disabled as it allows attackers to '
                    . 'bruteforce login to any MySQL server. If you feel this is necessary, '
                    . 'use %srestrict login to MySQL server%s or %strusted proxies list%s. '
                    . 'However, IP-based protection with trusted proxies list may not be '
                    . 'reliable if your IP belongs to an ISP where thousands of users, '
                    . 'including you, are connected to.'
                ),
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                '[/a]',
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                '[/a]',
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                '[/a]'
            );
            SetupIndex::messagesSet(
                'notice',
                'AllowArbitraryServer',
                Descriptions::get('AllowArbitraryServer'),
                Sanitize::sanitizeMessage($sAllowArbitraryServerWarn)
            );
        }

        $this->performConfigChecksLoginCookie();

        $sDirectoryNotice = __(
            'This value should be double checked to ensure that this directory is '
            . 'neither world accessible nor readable or writable by other users on '
            . 'your server.'
        );

        // $cfg['SaveDir']
        // should not be world-accessible
        if ($this->cfg->getValue('SaveDir') != '') {
            SetupIndex::messagesSet(
                'notice',
                'SaveDir',
                Descriptions::get('SaveDir'),
                Sanitize::sanitizeMessage($sDirectoryNotice)
            );
        }

        // $cfg['TempDir']
        // should not be world-accessible
        if ($this->cfg->getValue('TempDir') != '') {
            SetupIndex::messagesSet(
                'notice',
                'TempDir',
                Descriptions::get('TempDir'),
                Sanitize::sanitizeMessage($sDirectoryNotice)
            );
        }

        $this->performConfigChecksZips();
    }

    /**
     * Check config of servers
     *
     * @param bool   $cookieAuthUsed    Cookie auth is used
     * @param string $blowfishSecret    Blowfish secret
     * @param bool   $blowfishSecretSet Blowfish secret set
     *
     * @return array
     */
    protected function performConfigChecksServers(
        $cookieAuthUsed,
        $blowfishSecret,
        $blowfishSecretSet
    ) {
        $serverCnt = $this->cfg->getServerCount();
        $isCookieAuthUsed = (int) $cookieAuthUsed;
        for ($i = 1; $i <= $serverCnt; $i++) {
            $cookieAuthServer = ($this->cfg->getValue('Servers/' . $i . '/auth_type') === 'cookie');
            $isCookieAuthUsed |= (int) $cookieAuthServer;
            $serverName = $this->performConfigChecksServersGetServerName(
                $this->cfg->getServerName($i),
                $i
            );
            $serverName = htmlspecialchars($serverName);

            [$blowfishSecret, $blowfishSecretSet] = $this->performConfigChecksServersSetBlowfishSecret(
                $blowfishSecret,
                $cookieAuthServer,
                $blowfishSecretSet
            );

            // $cfg['Servers'][$i]['ssl']
            // should be enabled if possible
            if (! $this->cfg->getValue('Servers/' . $i . '/ssl')) {
                $title = Descriptions::get('Servers/1/ssl') . ' (' . $serverName . ')';
                SetupIndex::messagesSet(
                    'notice',
                    'Servers/' . $i . '/ssl',
                    $title,
                    __(
                        'You should use SSL connections if your database server supports it.'
                    )
                );
            }

            $sSecurityInfoMsg = Sanitize::sanitizeMessage(sprintf(
                __(
                    'If you feel this is necessary, use additional protection settings - '
                    . '%1$shost authentication%2$s settings and %3$strusted proxies list%4$s. '
                    . 'However, IP-based protection may not be reliable if your IP belongs '
                    . 'to an ISP where thousands of users, including you, are connected to.'
                ),
                '[a@' . Url::getCommon(['page' => 'servers', 'mode' => 'edit', 'id' => $i]) . '#tab_Server_config]',
                '[/a]',
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                '[/a]'
            ));

            // $cfg['Servers'][$i]['auth_type']
            // warn about full user credentials if 'auth_type' is 'config'
            if (
                $this->cfg->getValue('Servers/' . $i . '/auth_type') === 'config'
                && $this->cfg->getValue('Servers/' . $i . '/user') != ''
                && $this->cfg->getValue('Servers/' . $i . '/password') != ''
            ) {
                $title = Descriptions::get('Servers/1/auth_type')
                    . ' (' . $serverName . ')';
                SetupIndex::messagesSet(
                    'notice',
                    'Servers/' . $i . '/auth_type',
                    $title,
                    Sanitize::sanitizeMessage(sprintf(
                        __(
                            'You set the [kbd]config[/kbd] authentication type and included '
                            . 'username and password for auto-login, which is not a desirable '
                            . 'option for live hosts. Anyone who knows or guesses your phpMyAdmin '
                            . 'URL can directly access your phpMyAdmin panel. Set %1$sauthentication '
                            . 'type%2$s to [kbd]cookie[/kbd] or [kbd]http[/kbd].'
                        ),
                        '[a@' . Url::getCommon(['page' => 'servers', 'mode' => 'edit', 'id' => $i]) . '#tab_Server]',
                        '[/a]'
                    ))
                    . ' ' . $sSecurityInfoMsg
                );
            }

            // $cfg['Servers'][$i]['AllowRoot']
            // $cfg['Servers'][$i]['AllowNoPassword']
            // serious security flaw
            if (
                ! $this->cfg->getValue('Servers/' . $i . '/AllowRoot')
                || ! $this->cfg->getValue('Servers/' . $i . '/AllowNoPassword')
            ) {
                continue;
            }

            $title = Descriptions::get('Servers/1/AllowNoPassword')
                . ' (' . $serverName . ')';
            SetupIndex::messagesSet(
                'notice',
                'Servers/' . $i . '/AllowNoPassword',
                $title,
                __('You allow for connecting to the server without a password.')
                . ' ' . $sSecurityInfoMsg
            );
        }

        return [
            (bool) $isCookieAuthUsed,
            $blowfishSecret,
            $blowfishSecretSet,
        ];
    }

    /**
     * Set blowfish secret
     *
     * @param string|null $blowfishSecret    Blowfish secret
     * @param bool        $cookieAuthServer  Cookie auth is used
     * @param bool        $blowfishSecretSet Blowfish secret set
     *
     * @return array
     */
    protected function performConfigChecksServersSetBlowfishSecret(
        $blowfishSecret,
        $cookieAuthServer,
        $blowfishSecretSet
    ): array {
        if (
            $cookieAuthServer
            && (! is_string($blowfishSecret) || mb_strlen($blowfishSecret, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        ) {
            $blowfishSecretSet = true;
            $this->cfg->set('blowfish_secret', sodium_crypto_secretbox_keygen());
        }

        return [
            $blowfishSecret,
            $blowfishSecretSet,
        ];
    }

    /**
     * Define server name
     *
     * @param string $serverName Server name
     * @param int    $serverId   Server id
     *
     * @return string Server name
     */
    protected function performConfigChecksServersGetServerName(
        $serverName,
        $serverId
    ) {
        if ($serverName === 'localhost') {
            return $serverName . ' [' . $serverId . ']';
        }

        return $serverName;
    }

    /**
     * Perform config checks for zip part.
     */
    protected function performConfigChecksZips(): void
    {
        $this->performConfigChecksServerGZipdump();
        $this->performConfigChecksServerBZipdump();
        $this->performConfigChecksServersZipdump();
    }

    /**
     * Perform config checks for zip part.
     */
    protected function performConfigChecksServersZipdump(): void
    {
        // $cfg['ZipDump']
        // requires zip_open in import
        if ($this->cfg->getValue('ZipDump') && ! $this->functionExists('zip_open')) {
            SetupIndex::messagesSet(
                'error',
                'ZipDump_import',
                Descriptions::get('ZipDump'),
                Sanitize::sanitizeMessage(sprintf(
                    __(
                        '%sZip decompression%s requires functions (%s) which are unavailable on this system.'
                    ),
                    '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Import_export]',
                    '[/a]',
                    'zip_open'
                ))
            );
        }

        // $cfg['ZipDump']
        // requires gzcompress in export
        if (! $this->cfg->getValue('ZipDump') || $this->functionExists('gzcompress')) {
            return;
        }

        SetupIndex::messagesSet(
            'error',
            'ZipDump_export',
            Descriptions::get('ZipDump'),
            Sanitize::sanitizeMessage(sprintf(
                __(
                    '%sZip compression%s requires functions (%s) which are unavailable on this system.'
                ),
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Import_export]',
                '[/a]',
                'gzcompress'
            ))
        );
    }

    /**
     * Check config of servers
     *
     * @param bool   $cookieAuthUsed    Cookie auth is used
     * @param bool   $blowfishSecretSet Blowfish secret set
     * @param string $blowfishSecret    Blowfish secret
     */
    protected function performConfigChecksCookieAuthUsed(
        $cookieAuthUsed,
        $blowfishSecretSet,
        $blowfishSecret
    ): void {
        // $cfg['blowfish_secret']
        // it's required for 'cookie' authentication
        if (! $cookieAuthUsed || ! $blowfishSecretSet) {
            return;
        }

        // 'cookie' auth used, blowfish_secret was generated
        SetupIndex::messagesSet(
            'notice',
            'blowfish_secret_created',
            Descriptions::get('blowfish_secret'),
            Sanitize::sanitizeMessage(__(
                'You didn\'t have blowfish secret set and have enabled '
                . '[kbd]cookie[/kbd] authentication, so a key was automatically '
                . 'generated for you. It is used to encrypt cookies; you don\'t need to '
                . 'remember it.'
            ))
        );
    }

    /**
     * Check configuration for login cookie
     */
    protected function performConfigChecksLoginCookie(): void
    {
        // $cfg['LoginCookieValidity']
        // value greater than session.gc_maxlifetime will cause
        // random session invalidation after that time
        $loginCookieValidity = $this->cfg->getValue('LoginCookieValidity');
        if ($loginCookieValidity > ini_get('session.gc_maxlifetime')) {
            SetupIndex::messagesSet(
                'error',
                'LoginCookieValidity',
                Descriptions::get('LoginCookieValidity'),
                Sanitize::sanitizeMessage(sprintf(
                    __(
                        '%1$sLogin cookie validity%2$s greater than %3$ssession.gc_maxlifetime%4$s may '
                        . 'cause random session invalidation (currently session.gc_maxlifetime '
                        . 'is %5$d).'
                    ),
                    '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                    '[/a]',
                    '[a@' . Core::getPHPDocLink('session.configuration.php#ini.session.gc-maxlifetime') . ']',
                    '[/a]',
                    ini_get('session.gc_maxlifetime')
                ))
            );
        }

        // $cfg['LoginCookieValidity']
        // should be at most 1800 (30 min)
        if ($loginCookieValidity > 1800) {
            SetupIndex::messagesSet(
                'notice',
                'LoginCookieValidity',
                Descriptions::get('LoginCookieValidity'),
                Sanitize::sanitizeMessage(sprintf(
                    __(
                        '%sLogin cookie validity%s should be set to 1800 seconds (30 minutes) '
                        . 'at most. Values larger than 1800 may pose a security risk such as '
                        . 'impersonation.'
                    ),
                    '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                    '[/a]'
                ))
            );
        }

        // $cfg['LoginCookieValidity']
        // $cfg['LoginCookieStore']
        // LoginCookieValidity must be less or equal to LoginCookieStore
        if (
            ($this->cfg->getValue('LoginCookieStore') == 0)
            || ($loginCookieValidity <= $this->cfg->getValue('LoginCookieStore'))
        ) {
            return;
        }

        SetupIndex::messagesSet(
            'error',
            'LoginCookieValidity',
            Descriptions::get('LoginCookieValidity'),
            Sanitize::sanitizeMessage(sprintf(
                __(
                    'If using [kbd]cookie[/kbd] authentication and %sLogin cookie store%s '
                    . 'is not 0, %sLogin cookie validity%s must be set to a value less or '
                    . 'equal to it.'
                ),
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                '[/a]',
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Security]',
                '[/a]'
            ))
        );
    }

    /**
     * Check GZipDump configuration
     */
    protected function performConfigChecksServerBZipdump(): void
    {
        // $cfg['BZipDump']
        // requires bzip2 functions
        if (
            ! $this->cfg->getValue('BZipDump')
            || ($this->functionExists('bzopen') && $this->functionExists('bzcompress'))
        ) {
            return;
        }

        $functions = $this->functionExists('bzopen')
            ? '' :
            'bzopen';
        $functions .= $this->functionExists('bzcompress')
            ? ''
            : ($functions ? ', ' : '') . 'bzcompress';
        SetupIndex::messagesSet(
            'error',
            'BZipDump',
            Descriptions::get('BZipDump'),
            Sanitize::sanitizeMessage(
                sprintf(
                    __(
                        '%1$sBzip2 compression and decompression%2$s requires functions (%3$s) which '
                         . 'are unavailable on this system.'
                    ),
                    '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Import_export]',
                    '[/a]',
                    $functions
                )
            )
        );
    }

    /**
     * Check GZipDump configuration
     */
    protected function performConfigChecksServerGZipdump(): void
    {
        // $cfg['GZipDump']
        // requires zlib functions
        if (
            ! $this->cfg->getValue('GZipDump')
            || ($this->functionExists('gzopen') && $this->functionExists('gzencode'))
        ) {
            return;
        }

        SetupIndex::messagesSet(
            'error',
            'GZipDump',
            Descriptions::get('GZipDump'),
            Sanitize::sanitizeMessage(sprintf(
                __(
                    '%1$sGZip compression and decompression%2$s requires functions (%3$s) which '
                    . 'are unavailable on this system.'
                ),
                '[a@' . Url::getCommon(['page' => 'form', 'formset' => 'Features']) . '#tab_Import_export]',
                '[/a]',
                'gzencode'
            ))
        );
    }

    /**
     * Wrapper around function_exists to allow mock in test
     *
     * @param string $name Function name
     */
    protected function functionExists($name): bool
    {
        return function_exists($name);
    }
}
