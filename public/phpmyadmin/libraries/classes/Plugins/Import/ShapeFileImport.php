<?php
/**
 * This class extends ShapeFile class to cater the following phpMyAdmin
 * specific requirements.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\ShapeFile\ShapeFile;

/**
 * ShapeFileImport class
 */
class ShapeFileImport extends ShapeFile
{
    /**
     * Reads given number of bytes from SHP file
     *
     * @param int $bytes number of bytes
     *
     * @return string|false
     */
    public function readSHP(int $bytes)
    {
        return ImportShp::readFromBuffer($bytes);
    }

    /**
     * Checks whether file is at EOF
     */
    public function eofSHP(): bool
    {
        global $eof;

        return (bool) $eof;
    }
}
