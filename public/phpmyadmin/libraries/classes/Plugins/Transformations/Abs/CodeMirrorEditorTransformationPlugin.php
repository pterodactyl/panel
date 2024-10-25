<?php
/**
 * Abstract class for syntax highlighted editors using CodeMirror
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;

use function htmlspecialchars;
use function strtolower;

/**
 * Provides common methods for all the CodeMirror syntax highlighted editors
 */
abstract class CodeMirrorEditorTransformationPlugin extends IOTransformationsPlugin
{
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
        if (! empty($value)) {
            $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                . '" value="' . htmlspecialchars($value) . '">';
        }

        $class = 'transform_' . strtolower(static::getName()) . '_editor';

        return $html . '<textarea name="fields' . $column_name_appendix . '"'
            . ' dir="' . $text_dir . '" class="' . $class . '">'
            . htmlspecialchars($value) . '</textarea>';
    }
}
