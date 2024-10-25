<?php
/**
 * Text Plain Prepend/Append Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations;

use PhpMyAdmin\Plugins\Transformations\Abs\PreApPendTransformationsPlugin;

/**
 * Handles the prepend and/or append transformation for text plain.
 * Has two options: the text to be prepended and appended (if any, default '')
 */
class Text_Plain_PreApPend extends PreApPendTransformationsPlugin
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
