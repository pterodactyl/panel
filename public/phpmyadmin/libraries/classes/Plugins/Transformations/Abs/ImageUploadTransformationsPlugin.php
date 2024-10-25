<?php
/**
 * Abstract class for the image upload input transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Url;

use function __;
use function bin2hex;
use function intval;

/**
 * Provides common methods for all of the image upload transformations plugins.
 */
abstract class ImageUploadTransformationsPlugin extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Image upload functionality which also displays a thumbnail.'
            . ' The options are the width and height of the thumbnail'
            . ' in pixels. Defaults to 100 X 100.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param array              $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = [], ?FieldMetadata $meta = null)
    {
        return $buffer;
    }

    /**
     * Returns the html for input field to override default textarea.
     * Note: Return empty string if default textarea is required.
     *
     * @param array  $column               column details
     * @param int    $row_id               row number
     * @param string $column_name_appendix the name attribute
     * @param array  $options              transformation options
     * @param string $value                Current field value
     * @param string $text_dir             text direction
     * @param int    $tabindex             tab index
     * @param int    $tabindex_for_value   offset for the values tabindex
     * @param int    $idindex              id index
     *
     * @return string the html for input field
     */
    public function getInputHtml(
        array $column,
        $row_id,
        $column_name_appendix,
        array $options,
        $value,
        $text_dir,
        $tabindex,
        $tabindex_for_value,
        $idindex
    ) {
        $html = '';
        $src = '';
        if (! empty($value)) {
            $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                . '" value="' . bin2hex($value) . '">';
            $html .= '<input type="hidden" name="fields' . $column_name_appendix
                . '" value="' . bin2hex($value) . '">';
            $src = Url::getFromRoute('/transformation/wrapper', $options['wrapper_params']);
        }

        $html .= '<img src="' . $src . '" width="'
            . (isset($options[0]) ? intval($options[0]) : '100') . '" height="'
            . (isset($options[1]) ? intval($options[1]) : '100') . '" alt="'
            . __('Image preview here') . '">';
        $html .= '<br><input type="file" name="fields_upload'
            . $column_name_appendix . '" accept="image/*" class="image-upload">';

        return $html;
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return array javascripts to be included
     */
    public function getScripts()
    {
        return ['transformations/image_upload.js'];
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'Image upload';
    }
}
