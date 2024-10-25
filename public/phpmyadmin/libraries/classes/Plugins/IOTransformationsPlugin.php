<?php
/**
 * Abstract class for the I/O transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

/**
 * Provides a common interface that will have to be implemented
 * by all of the Input/Output transformations plugins.
 */
abstract class IOTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Specifies whether transformation was successful or not.
     *
     * @var bool
     */
    protected $success = true;

    /**
     * To store the error message in case of failed transformations.
     *
     * @var string
     */
    protected $error = '';

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
        return '';
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return array javascripts to be included
     */
    public function getScripts()
    {
        return [];
    }

    /**
     * Returns the error message
     *
     * @return string error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the success status
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Resets the object properties
     */
    public function reset(): void
    {
        $this->success = true;
        $this->error = '';
    }
}
