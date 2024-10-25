<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms;

use PhpMyAdmin\Config\ConfigFile;

use function array_merge;
use function class_exists;
use function in_array;

class BaseFormList
{
    /**
     * List of all forms
     *
     * @var string[]
     */
    protected static $all = [];

    /** @var string */
    protected static $ns = 'PhpMyAdmin\\Config\\Forms\\';

    /** @var array */
    private $forms;

    /**
     * @return string[]
     */
    public static function getAll()
    {
        return static::$all;
    }

    /**
     * @param string $name Name
     */
    public static function isValid($name): bool
    {
        return in_array($name, static::$all);
    }

    /**
     * @param string $name Name
     *
     * @return string|null
     * @psalm-return class-string<BaseForm>|null
     */
    public static function get($name)
    {
        if (static::isValid($name)) {
            /** @var class-string<BaseForm> $class */
            $class = static::$ns . $name . 'Form';

            return $class;
        }

        return null;
    }

    /**
     * @param ConfigFile $cf Config file instance
     */
    final public function __construct(ConfigFile $cf)
    {
        $this->forms = [];
        foreach (static::$all as $form) {
            $class = (string) static::get($form);
            if (! class_exists($class)) {
                continue;
            }

            $this->forms[] = new $class($cf);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allowPartialSave allows for partial form saving
     *                               on failed validation
     * @param bool $checkFormSubmit  whether check for $_POST['submit_save']
     */
    public function process($allowPartialSave = true, $checkFormSubmit = true): bool
    {
        $ret = true;
        foreach ($this->forms as $form) {
            $ret = $ret && $form->process($allowPartialSave, $checkFormSubmit);
        }

        return $ret;
    }

    /**
     * Displays errors
     *
     * @return string HTML for errors
     */
    public function displayErrors()
    {
        $ret = '';
        foreach ($this->forms as $form) {
            $ret .= $form->displayErrors();
        }

        return $ret;
    }

    /**
     * Reverts erroneous fields to their default values
     */
    public function fixErrors(): void
    {
        foreach ($this->forms as $form) {
            $form->fixErrors();
        }
    }

    /**
     * Tells whether form validation failed
     */
    public function hasErrors(): bool
    {
        $ret = false;
        foreach ($this->forms as $form) {
            $ret = $ret || $form->hasErrors();
        }

        return $ret;
    }

    /**
     * Returns list of fields used in the form.
     *
     * @return string[]
     */
    public static function getFields()
    {
        $names = [];
        foreach (static::$all as $form) {
            $class = (string) static::get($form);
            if (! class_exists($class)) {
                continue;
            }

            $names = array_merge($names, $class::getFields());
        }

        return $names;
    }
}
