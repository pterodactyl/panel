<?php
/**
 * Form handling code.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use function array_combine;
use function array_shift;
use function array_walk;
use function count;
use function gettype;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function ltrim;
use function mb_strpos;
use function mb_strrpos;
use function mb_substr;
use function str_replace;
use function trigger_error;

use const E_USER_ERROR;
use const E_USER_WARNING;
use const PHP_VERSION_ID;

/**
 * Base class for forms, loads default configuration options, checks allowed
 * values etc.
 */
class Form
{
    /**
     * Form name
     *
     * @var string
     */
    public $name;

    /**
     * Arbitrary index, doesn't affect class' behavior
     *
     * @var int
     */
    public $index;

    /**
     * Form fields (paths), filled by {@link readFormPaths()}, indexed by field name
     *
     * @var array
     */
    public $fields;

    /**
     * Stores default values for some fields (eg. pmadb tables)
     *
     * @var array
     */
    public $default;

    /**
     * Caches field types, indexed by field names
     *
     * @var array
     */
    private $fieldsTypes;

    /**
     * ConfigFile instance
     *
     * @var ConfigFile
     */
    private $configFile;

    /**
     * A counter for the number of groups
     *
     * @var int
     */
    private static $groupCounter = 0;

    /**
     * Reads default config values
     *
     * @param string     $formName Form name
     * @param array      $form     Form data
     * @param ConfigFile $cf       Config file instance
     * @param int        $index    arbitrary index, stored in Form::$index
     */
    public function __construct(
        $formName,
        array $form,
        ConfigFile $cf,
        $index = null
    ) {
        $this->index = $index;
        $this->configFile = $cf;
        $this->loadForm($formName, $form);
    }

    /**
     * Returns type of given option
     *
     * @param string $optionName path or field name
     *
     * @return string|null one of: boolean, integer, double, string, select, array
     */
    public function getOptionType($optionName)
    {
        $key = ltrim(
            mb_substr(
                $optionName,
                (int) mb_strrpos($optionName, '/')
            ),
            '/'
        );

        return $this->fieldsTypes[$key] ?? null;
    }

    /**
     * Returns allowed values for select fields
     *
     * @param string $optionPath Option path
     *
     * @return array
     */
    public function getOptionValueList($optionPath)
    {
        $value = $this->configFile->getDbEntry($optionPath);
        if ($value === null) {
            trigger_error(
                $optionPath . ' - select options not defined',
                PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING
            );

            return [];
        }

        if (! is_array($value)) {
            trigger_error(
                $optionPath . ' - not a static value list',
                PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING
            );

            return [];
        }

        // convert array('#', 'a', 'b') to array('a', 'b')
        if (isset($value[0]) && $value[0] === '#') {
            // remove first element ('#')
            array_shift($value);

            // $value has keys and value names, return it
            return $value;
        }

        // convert value list array('a', 'b') to array('a' => 'a', 'b' => 'b')
        $hasStringKeys = false;
        $keys = [];
        for ($i = 0, $nb = count($value); $i < $nb; $i++) {
            if (! isset($value[$i])) {
                $hasStringKeys = true;
                break;
            }

            $keys[] = is_bool($value[$i]) ? (int) $value[$i] : $value[$i];
        }

        if (! $hasStringKeys) {
            /** @var array $value */
            $value = array_combine($keys, $value);
        }

        // $value has keys and value names, return it
        return $value;
    }

    /**
     * array_walk callback function, reads path of form fields from
     * array (see docs for \PhpMyAdmin\Config\Forms\BaseForm::getForms)
     *
     * @param mixed $value  Value
     * @param mixed $key    Key
     * @param mixed $prefix Prefix
     */
    private function readFormPathsCallback($value, $key, $prefix): void
    {
        if (is_array($value)) {
            $prefix .= $key . '/';
            array_walk(
                $value,
                function ($value, $key, $prefix): void {
                    $this->readFormPathsCallback($value, $key, $prefix);
                },
                $prefix
            );

            return;
        }

        if (! is_int($key)) {
            $this->default[$prefix . $key] = $value;
            $value = $key;
        }

        // add unique id to group ends
        if ($value === ':group:end') {
            $value .= ':' . self::$groupCounter++;
        }

        $this->fields[] = $prefix . $value;
    }

    /**
     * Reset the group counter, function for testing purposes
     */
    public static function resetGroupCounter(): void
    {
        self::$groupCounter = 0;
    }

    /**
     * Reads form paths to {@link $fields}
     *
     * @param array $form Form
     */
    protected function readFormPaths(array $form): void
    {
        // flatten form fields' paths and save them to $fields
        $this->fields = [];
        array_walk(
            $form,
            function ($value, $key, $prefix): void {
                $this->readFormPathsCallback($value, $key, $prefix);
            },
            ''
        );

        // $this->fields is an array of the form: [0..n] => 'field path'
        // change numeric indexes to contain field names (last part of the path)
        $paths = $this->fields;
        $this->fields = [];
        foreach ($paths as $path) {
            $key = ltrim(
                mb_substr($path, (int) mb_strrpos($path, '/')),
                '/'
            );
            $this->fields[$key] = $path;
        }
        // now $this->fields is an array of the form: 'field name' => 'field path'
    }

    /**
     * Reads fields' types to $this->fieldsTypes
     */
    protected function readTypes(): void
    {
        $cf = $this->configFile;
        foreach ($this->fields as $name => $path) {
            if (mb_strpos((string) $name, ':group:') === 0) {
                $this->fieldsTypes[$name] = 'group';
                continue;
            }

            $v = $cf->getDbEntry($path);
            if ($v !== null) {
                $type = is_array($v) ? 'select' : $v;
            } else {
                $type = gettype($cf->getDefault($path));
            }

            $this->fieldsTypes[$name] = $type;
        }
    }

    /**
     * Remove slashes from group names
     *
     * @see issue #15836
     *
     * @param array $form The form data
     *
     * @return array
     */
    protected function cleanGroupPaths(array $form): array
    {
        foreach ($form as &$name) {
            if (! is_string($name)) {
                continue;
            }

            if (mb_strpos($name, ':group:') !== 0) {
                continue;
            }

            $name = str_replace('/', '-', $name);
        }

        return $form;
    }

    /**
     * Reads form settings and prepares class to work with given subset of
     * config file
     *
     * @param string $formName Form name
     * @param array  $form     Form
     */
    public function loadForm($formName, array $form): void
    {
        $this->name = $formName;
        $form = $this->cleanGroupPaths($form);
        $this->readFormPaths($form);
        $this->readTypes();
    }
}
