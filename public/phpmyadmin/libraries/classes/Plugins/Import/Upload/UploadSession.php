<?php
/**
 * Provides upload functionalities for the import plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import\Upload;

use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Plugins\UploadInterface;

use function array_key_exists;
use function ini_get;
use function trim;

/**
 * Implementation for session
 */
class UploadSession implements UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey()
    {
        return (string) ini_get('session.upload_progress.name');
    }

    /**
     * Returns upload status.
     *
     * This is implementation for session.upload_progress in PHP 5.4+.
     *
     * @param string $id upload id
     *
     * @return array|null
     */
    public static function getUploadStatus($id)
    {
        global $SESSION_KEY;

        if (trim($id) == '') {
            return null;
        }

        if (! array_key_exists($id, $_SESSION[$SESSION_KEY])) {
            $_SESSION[$SESSION_KEY][$id] = [
                'id' => $id,
                'finished' => false,
                'percent' => 0,
                'total' => 0,
                'complete' => 0,
                'plugin' => self::getIdKey(),
            ];
        }

        $ret = $_SESSION[$SESSION_KEY][$id];

        if (! Ajax::sessionCheck() || $ret['finished']) {
            return $ret;
        }

        $status = false;
        $sessionkey = ini_get('session.upload_progress.prefix') . $id;

        if (isset($_SESSION[$sessionkey])) {
            $status = $_SESSION[$sessionkey];
        }

        if ($status) {
            $ret['finished'] = $status['done'];
            $ret['total'] = $status['content_length'];
            $ret['complete'] = $status['bytes_processed'];

            if ($ret['total'] > 0) {
                $ret['percent'] = $ret['complete'] / $ret['total'] * 100;
            }
        } else {
            $ret = [
                'id' => $id,
                'finished' => true,
                'percent' => 100,
                'total' => $ret['total'],
                'complete' => $ret['total'],
                'plugin' => self::getIdKey(),
            ];
        }

        $_SESSION[$SESSION_KEY][$id] = $ret;

        return $ret;
    }
}
