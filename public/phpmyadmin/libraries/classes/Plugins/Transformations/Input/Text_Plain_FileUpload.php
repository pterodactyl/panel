<?php
/**
 * Text Plain File Upload Input Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\Plugins\Transformations\Abs\TextFileUploadTransformationsPlugin;

/**
 * Handles the input text file upload transformation for text plain.
 */
class Text_Plain_FileUpload extends TextFileUploadTransformationsPlugin
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
