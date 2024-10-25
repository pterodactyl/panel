<?php
/**
 * Handles the IPv4/IPv6 to long transformation for text plain
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Utils\FormatConverter;

use function __;
use function htmlspecialchars;

/**
 * Handles the IPv4/IPv6 to long transformation for text plain
 */
class Text_Plain_Iptolong extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __('Converts an Internet network address in (IPv4/IPv6) format into a long integer.');
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string        $buffer  text to be transformed. a binary string containing
     *                               an IP address, as returned from MySQL's INET6_ATON
     *                               function
     * @param array         $options transformation options
     * @param FieldMetadata $meta    meta information
     *
     * @return string IP address
     */
    public function applyTransformation($buffer, array $options = [], ?FieldMetadata $meta = null)
    {
        return (string) FormatConverter::ipToLong($buffer);
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
        $val = '';

        if (! empty($value)) {
            $val = FormatConverter::longToIp($value);

            if ($value !== $val) {
                $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                    . '" value="' . htmlspecialchars($val) . '"/>';
            }
        }

        return $html . '<input type="text" name="fields' . $column_name_appendix . '"'
            . ' value="' . htmlspecialchars($val) . '"'
            . ' size="40"'
            . ' dir="' . $text_dir . '"'
            . ' class="transform_IPToLong"'
            . ' id="field_' . $idindex . '_3"'
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '" />';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'IPv4/IPv6 To Long';
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
