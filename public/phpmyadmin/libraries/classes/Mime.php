<?php
/**
 * MIME detection code.
 *
 * @todo Maybe we could try to use fileinfo module if loaded
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function chr;
use function mb_strlen;
use function mb_substr;
use function substr;

/**
 * PhpMyAdmin\Mime class;
 */
class Mime
{
    /**
     * Tries to detect MIME type of content.
     *
     * @param string $test First few bytes of content to use for detection
     *
     * @return string
     */
    public static function detect(&$test)
    {
        $len = mb_strlen($test);
        if ($len >= 2 && $test[0] == chr(0xff) && $test[1] == chr(0xd8)) {
            return 'image/jpeg';
        }

        if ($len >= 3 && substr($test, 0, 3) === 'GIF') {
            return 'image/gif';
        }

        if ($len >= 4 && mb_substr($test, 0, 4, '8bit') === "\x89PNG") {
            return 'image/png';
        }

        return 'application/octet-stream';
    }
}
