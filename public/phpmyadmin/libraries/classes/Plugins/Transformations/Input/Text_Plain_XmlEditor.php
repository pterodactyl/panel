<?php
/**
 * XML (and HTML) editing with syntax highlighted CodeMirror editor
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\Plugins\Transformations\Abs\CodeMirrorEditorTransformationPlugin;

use function __;

/**
 * XML (and HTML) editing with syntax highlighted CodeMirror editor
 */
class Text_Plain_XmlEditor extends CodeMirrorEditorTransformationPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __('Syntax highlighted CodeMirror editor for XML (and HTML).');
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return array javascripts to be included
     */
    public function getScripts()
    {
        $scripts = [];
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $scripts[] = 'vendor/codemirror/lib/codemirror.js';
            $scripts[] = 'vendor/codemirror/mode/xml/xml.js';
            $scripts[] = 'transformations/xml_editor.js';
        }

        return $scripts;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'XML';
    }

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
