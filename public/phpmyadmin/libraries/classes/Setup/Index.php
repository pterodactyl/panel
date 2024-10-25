<?php
/**
 * Various checks and message functions used on index page.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Setup;

use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Version;
use PhpMyAdmin\VersionInformation;

use function __;
use function htmlspecialchars;
use function is_array;
use function sprintf;
use function uniqid;

/**
 * PhpMyAdmin\Setup\Index class
 *
 * Various checks and message functions used on index page.
 */
class Index
{
    /**
     * Initializes message list
     */
    public static function messagesBegin(): void
    {
        if (! isset($_SESSION['messages']) || ! is_array($_SESSION['messages'])) {
            $_SESSION['messages'] = [
                'error' => [],
                'notice' => [],
            ];
        } else {
            // reset message states
            foreach ($_SESSION['messages'] as &$messages) {
                foreach ($messages as &$msg) {
                    $msg['fresh'] = false;
                    $msg['active'] = false;
                }
            }
        }
    }

    /**
     * Adds a new message to message list
     *
     * @param string $type    one of: notice, error
     * @param string $msgId   unique message identifier
     * @param string $title   language string id (in $str array)
     * @param string $message message text
     */
    public static function messagesSet($type, $msgId, $title, $message): void
    {
        $fresh = ! isset($_SESSION['messages'][$type][$msgId]);
        $_SESSION['messages'][$type][$msgId] = [
            'fresh' => $fresh,
            'active' => true,
            'title' => $title,
            'message' => $message,
        ];
    }

    /**
     * Cleans up message list
     */
    public static function messagesEnd(): void
    {
        foreach ($_SESSION['messages'] as &$messages) {
            $remove_ids = [];
            foreach ($messages as $id => $msg) {
                if ($msg['active'] != false) {
                    continue;
                }

                $remove_ids[] = $id;
            }

            foreach ($remove_ids as $id) {
                unset($messages[$id]);
            }
        }
    }

    /**
     * Prints message list, must be called after self::messagesEnd()
     *
     * @return array
     */
    public static function messagesShowHtml()
    {
        $return = [];
        foreach ($_SESSION['messages'] as $type => $messages) {
            foreach ($messages as $id => $msg) {
                $return[] = [
                    'id' => $id,
                    'title' => $msg['title'],
                    'type' => $type,
                    'message' => $msg['message'],
                    'is_hidden' => ! $msg['fresh'] && $type !== 'error',
                ];
            }
        }

        return $return;
    }

    /**
     * Checks for newest phpMyAdmin version and sets result as a new notice
     */
    public static function versionCheck(): void
    {
        // version check messages should always be visible so let's make
        // a unique message id each time we run it
        $message_id = uniqid('version_check');

        // Fetch data
        $versionInformation = new VersionInformation();
        $version_data = $versionInformation->getLatestVersion();

        if (empty($version_data)) {
            self::messagesSet(
                'error',
                $message_id,
                __('Version check'),
                __(
                    'Reading of version failed. Maybe you\'re offline or the upgrade server does not respond.'
                )
            );

            return;
        }

        $releases = $version_data->releases;
        $latestCompatible = $versionInformation->getLatestCompatibleVersion($releases);
        if ($latestCompatible == null) {
            return;
        }

        $version = $latestCompatible['version'];
        $date = $latestCompatible['date'];

        $version_upstream = $versionInformation->versionToInt($version);
        if ($version_upstream === false) {
            self::messagesSet(
                'error',
                $message_id,
                __('Version check'),
                __('Got invalid version string from server')
            );

            return;
        }

        $version_local = $versionInformation->versionToInt(Version::VERSION);
        if ($version_local === false) {
            self::messagesSet(
                'error',
                $message_id,
                __('Version check'),
                __('Unparsable version string')
            );

            return;
        }

        if ($version_upstream > $version_local) {
            $version = htmlspecialchars($version);
            $date = htmlspecialchars($date);
            self::messagesSet(
                'notice',
                $message_id,
                __('Version check'),
                sprintf(__('A newer version of phpMyAdmin is available and you should consider upgrading.'
                    . ' The newest version is %s, released on %s.'), $version, $date)
            );
        } else {
            if ($version_local % 100 == 0) {
                self::messagesSet(
                    'notice',
                    $message_id,
                    __('Version check'),
                    Sanitize::sanitizeMessage(sprintf(__('You are using Git version, run [kbd]git pull[/kbd]'
                        . ' :-)[br]The latest stable version is %s, released on %s.'), $version, $date))
                );
            } else {
                self::messagesSet(
                    'notice',
                    $message_id,
                    __('Version check'),
                    __('No newer stable version is available')
                );
            }
        }
    }
}
