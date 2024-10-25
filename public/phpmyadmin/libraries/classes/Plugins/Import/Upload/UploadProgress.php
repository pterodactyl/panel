<?php
/**
 * Provides upload functionalities for the import plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import\Upload;

use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Plugins\UploadInterface;

use function array_key_exists;
use function function_exists;
use function trim;

/**
 * Implementation for upload progress
 */
class UploadProgress implements UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey()
    {
        return 'UPLOAD_IDENTIFIER';
    }

    /**
     * Returns upload status.
     *
     * This is implementation for upload progress
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

        if (! Ajax::progressCheck() || $ret['finished']) {
            return $ret;
        }

        $status = null;
        // @see https://pecl.php.net/package/uploadprogress
        if (function_exists('uploadprogress_get_info')) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            $status = \uploadprogress_get_info($id);
        }

        if ($status) {
            $ret['finished'] = false;

            if ($status['bytes_uploaded'] == $status['bytes_total']) {
                $ret['finished'] = true;
            }

            $ret['total'] = $status['bytes_total'];
            $ret['complete'] = $status['bytes_uploaded'];

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
