<?php
/**
 * Text Plain Substring Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations;

use PhpMyAdmin\Plugins\Transformations\Abs\SubstringTransformationsPlugin;

/**
 * Handles the substring transformation for text plain
 */
class Text_Plain_Substring extends SubstringTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return 'Text';
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return 'Plain';
    }
}
