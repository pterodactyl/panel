<?php
/**
 * The top-level class of the "Plugin" subtree of the object-oriented
 * properties system (the other subtree is "Options").
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Plugins;

use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\PropertyItem;

/**
 * Superclass for
 *  - PhpMyAdmin\Properties\Plugins\ExportPluginProperties,
 *  - PhpMyAdmin\Properties\Plugins\ImportPluginProperties and
 *  - TransformationsPluginProperties
 */
abstract class PluginPropertyItem extends PropertyItem
{
    /**
     * Text
     *
     * @var string
     */
    private $text;
    /**
     * Extension
     *
     * @var string
     */
    private $extension;
    /**
     * Options
     *
     * @var OptionsPropertyRootGroup|null
     */
    private $options = null;
    /**
     * Options text
     *
     * @var string
     */
    private $optionsText;
    /**
     * MIME Type
     *
     * @var string
     */
    private $mimeType;

    /**
     * Gets the text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text
     *
     * @param string $text text
     */
    public function setText($text): void
    {
        $this->text = $text;
    }

    /**
     * Gets the extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Sets the extension
     *
     * @param string $extension extension
     */
    public function setExtension($extension): void
    {
        $this->extension = $extension;
    }

    /**
     * Gets the options
     *
     * @return OptionsPropertyRootGroup|null
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the options
     *
     * @param OptionsPropertyRootGroup $options options
     */
    public function setOptions($options): void
    {
        $this->options = $options;
    }

    /**
     * Gets the options text
     *
     * @return string
     */
    public function getOptionsText()
    {
        return $this->optionsText;
    }

    /**
     * Sets the options text
     *
     * @param string $optionsText optionsText
     */
    public function setOptionsText($optionsText): void
    {
        $this->optionsText = $optionsText;
    }

    /**
     * Gets the MIME type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Sets the MIME type
     *
     * @param string $mimeType MIME type
     */
    public function setMimeType($mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * Returns the property type ( either "options", or "plugin" ).
     *
     * @return string
     */
    public function getPropertyType()
    {
        return 'plugin';
    }

    /**
     * Whether each plugin has to be saved as a file
     */
    public function getForceFile(): bool
    {
        return false;
    }
}
