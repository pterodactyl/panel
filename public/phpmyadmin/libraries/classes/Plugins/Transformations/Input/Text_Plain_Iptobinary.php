<?php
/**
 * Handles the IPv4/IPv6 to binary transformation for text plain
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Utils\FormatConverter;

use function __;
use function htmlspecialchars;
use function inet_ntop;
use function pack;
use function strlen;

/**
 * Handles the IPv4/IPv6 to binary transformation for text plain
 */
class Text_Plain_Iptobinary extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __('Converts an Internet network address in (IPv4/IPv6) format to binary');
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed. a binary string containing
     *                                    an IP address, as returned from MySQL's INET6_ATON
     *                                    function
     * @param array              $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string IP address
     */
    public function applyTransformation($buffer, array $options = [], ?FieldMetadata $meta = null)
    {
        return FormatConverter::ipToBinary($buffer);
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
            $length = strlen($value);
            if ($length == 4 || $length == 16) {
                $ip = @inet_ntop(pack('A' . $length, $value));
                if ($ip !== false) {
                    $val = $ip;
                }
            }

            $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                . '" value="' . htmlspecialchars($val) . '">';
        }

        $class = 'transform_IPToBin';

        return $html . '<input type="text" name="fields' . $column_name_appendix . '"'
            . ' value="' . htmlspecialchars($val) . '"'
            . ' size="40"'
            . ' dir="' . $text_dir . '"'
            . ' class="' . $class . '"'
            . ' id="field_' . $idindex . '_3"'
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '">';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'IPv4/IPv6 To Binary';
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
