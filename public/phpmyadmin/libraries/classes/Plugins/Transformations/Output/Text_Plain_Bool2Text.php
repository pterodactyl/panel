<?php
/**
 * Text Plain Bool2Text Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\Bool2TextTransformationsPlugin;

/**
 * Handles the Boolean to Text transformation for text plain.
 * Has one option: the output format (default 'T/F')
 * or 'Y/N'
 */
class Text_Plain_Bool2Text extends Bool2TextTransformationsPlugin
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
