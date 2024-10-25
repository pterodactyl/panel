<?php
/**
 * The top-level class of the "Options" subtree of the object-oriented
 * properties system (the other subtree is "Plugin").
 */

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options;

use PhpMyAdmin\Properties\PropertyItem;

/**
 * Superclass for
 *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem and
 *  - OptionsProperty Group
 */
abstract class OptionsPropertyItem extends PropertyItem
{
    /**
     * Name
     *
     * @var string|null
     */
    private $name;
    /**
     * Text
     *
     * @var string|null
     */
    private $text;
    /**
     * What to force
     *
     * @var string|null
     */
    private $force;

    /**
     * @param string $name Item name
     * @param string $text Item text
     */
    public function __construct($name = null, $text = null)
    {
        if ($name) {
            $this->name = $name;
        }

        if (! $text) {
            return;
        }

        $this->text = $text;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the text
     *
     * @return string|null
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
     * Gets the force parameter
     *
     * @return string|null
     */
    public function getForce()
    {
        return $this->force;
    }

    /**
     * Sets the force parameter
     *
     * @param string $force force parameter
     */
    public function setForce($force): void
    {
        $this->force = $force;
    }

    /**
     * Returns the property type ( either "options", or "plugin" ).
     *
     * @return string
     */
    public function getPropertyType()
    {
        return 'options';
    }
}
