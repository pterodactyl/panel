<?php
/**
 * Config file management
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Core;

use function array_diff;
use function array_flip;
use function array_keys;
use function count;
use function is_array;
use function preg_replace;

/**
 * Config file management class.
 * Stores its data in $_SESSION
 */
class ConfigFile
{
    /**
     * Stores default phpMyAdmin config
     *
     * @see Settings
     *
     * @var array
     */
    private $defaultCfg;

    /**
     * Stores allowed values for non-standard fields
     *
     * @var array
     */
    private $cfgDb;

    /**
     * Stores original PMA config, not modified by user preferences
     *
     * @var array|null
     */
    private $baseCfg;

    /**
     * Whether we are currently working in PMA Setup context
     *
     * @var bool
     */
    private $isInSetup;

    /**
     * Keys which will be always written to config file
     *
     * @var array
     */
    private $persistKeys = [];

    /**
     * Changes keys while updating config in {@link updateWithGlobalConfig()}
     * or reading by {@link getConfig()} or {@link getConfigArray()}
     *
     * @var array
     */
    private $cfgUpdateReadMapping = [];

    /**
     * Key filter for {@link set()}
     *
     * @var array|null
     */
    private $setFilter;

    /**
     * Instance id (key in $_SESSION array, separate for each server -
     * ConfigFile{server id})
     *
     * @var string
     */
    private $id;

    /**
     * @param array|null $baseConfig base configuration read from
     *                               {@link PhpMyAdmin\Config::$base_config},
     *                               use only when not in PMA Setup
     */
    public function __construct($baseConfig = null)
    {
        // load default config values
        $settings = new Settings([]);
        $this->defaultCfg = $settings->toArray();

        // load additional config information
        $this->cfgDb = include ROOT_PATH . 'libraries/config.values.php';

        // apply default values overrides
        if (count($this->cfgDb['_overrides'])) {
            foreach ($this->cfgDb['_overrides'] as $path => $value) {
                Core::arrayWrite($path, $this->defaultCfg, $value);
            }
        }

        $this->baseCfg = $baseConfig;
        $this->isInSetup = $baseConfig === null;
        $this->id = 'ConfigFile' . $GLOBALS['server'];
        if (isset($_SESSION[$this->id])) {
            return;
        }

        $_SESSION[$this->id] = [];
    }

    /**
     * Sets names of config options which will be placed in config file even if
     * they are set to their default values (use only full paths)
     *
     * @param array $keys the names of the config options
     */
    public function setPersistKeys(array $keys): void
    {
        // checking key presence is much faster than searching so move values
        // to keys
        $this->persistKeys = array_flip($keys);
    }

    /**
     * Returns flipped array set by {@link setPersistKeys()}
     *
     * @return array
     */
    public function getPersistKeysMap()
    {
        return $this->persistKeys;
    }

    /**
     * By default ConfigFile allows setting of all configuration keys, use
     * this method to set up a filter on {@link set()} method
     *
     * @param array|null $keys array of allowed keys or null to remove filter
     */
    public function setAllowedKeys($keys): void
    {
        if ($keys === null) {
            $this->setFilter = null;

            return;
        }

        // checking key presence is much faster than searching so move values
        // to keys
        $this->setFilter = array_flip($keys);
    }

    /**
     * Sets path mapping for updating config in
     * {@link updateWithGlobalConfig()} or reading
     * by {@link getConfig()} or {@link getConfigArray()}
     *
     * @param array $mapping Contains the mapping of "Server/config options"
     *                       to "Server/1/config options"
     */
    public function setCfgUpdateReadMapping(array $mapping): void
    {
        $this->cfgUpdateReadMapping = $mapping;
    }

    /**
     * Resets configuration data
     */
    public function resetConfigData(): void
    {
        $_SESSION[$this->id] = [];
    }

    /**
     * Sets configuration data (overrides old data)
     *
     * @param array $cfg Configuration options
     */
    public function setConfigData(array $cfg): void
    {
        $_SESSION[$this->id] = $cfg;
    }

    /**
     * Sets config value
     *
     * @param string $path          Path
     * @param mixed  $value         Value
     * @param string $canonicalPath Canonical path
     */
    public function set($path, $value, $canonicalPath = null): void
    {
        if ($canonicalPath === null) {
            $canonicalPath = $this->getCanonicalPath($path);
        }

        if ($this->setFilter !== null && ! isset($this->setFilter[$canonicalPath])) {
            return;
        }

        // if the path isn't protected it may be removed
        if (isset($this->persistKeys[$canonicalPath])) {
            Core::arrayWrite($path, $_SESSION[$this->id], $value);

            return;
        }

        $defaultValue = $this->getDefault($canonicalPath);
        $removePath = $value === $defaultValue;
        if ($this->isInSetup) {
            // remove if it has a default value or is empty
            $removePath = $removePath
                || (empty($value) && empty($defaultValue));
        } else {
            // get original config values not overwritten by user
            // preferences to allow for overwriting options set in
            // config.inc.php with default values
            $instanceDefaultValue = Core::arrayRead($canonicalPath, $this->baseCfg);
            // remove if it has a default value and base config (config.inc.php)
            // uses default value
            $removePath = $removePath
                && ($instanceDefaultValue === $defaultValue);
        }

        if ($removePath) {
            Core::arrayRemove($path, $_SESSION[$this->id]);

            return;
        }

        Core::arrayWrite($path, $_SESSION[$this->id], $value);
    }

    /**
     * Flattens multidimensional array, changes indices to paths
     * (eg. 'key/subkey').
     *
     * @param array  $array  Multidimensional array
     * @param string $prefix Prefix
     */
    private function getFlatArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! isset($value[0])) {
                $result += $this->getFlatArray($value, $prefix . $key . '/');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns default config in a flattened array
     */
    public function getFlatDefaultConfig(): array
    {
        return $this->getFlatArray($this->defaultCfg);
    }

    /**
     * Updates config with values read from given array
     * (config will contain differences to defaults from {@see \PhpMyAdmin\Config\Settings}).
     *
     * @param array $cfg Configuration
     */
    public function updateWithGlobalConfig(array $cfg): void
    {
        // load config array and flatten it
        $flatConfig = $this->getFlatArray($cfg);

        // save values map for translating a few user preferences paths,
        // should be complemented by code reading from generated config
        // to perform inverse mapping
        foreach ($flatConfig as $path => $value) {
            if (isset($this->cfgUpdateReadMapping[$path])) {
                $path = $this->cfgUpdateReadMapping[$path];
            }

            $this->set($path, $value, $path);
        }
    }

    /**
     * Returns config value or $default if it's not set
     *
     * @param string $path    Path of config file
     * @param mixed  $default Default values
     *
     * @return mixed
     */
    public function get($path, $default = null)
    {
        return Core::arrayRead($path, $_SESSION[$this->id], $default);
    }

    /**
     * Returns default config value or $default it it's not set ie. it doesn't
     * exist in {@see \PhpMyAdmin\Config\Settings} ($cfg) and config.values.php
     * ($_cfg_db['_overrides'])
     *
     * @param string $canonicalPath Canonical path
     * @param mixed  $default       Default value
     *
     * @return mixed
     */
    public function getDefault($canonicalPath, $default = null)
    {
        return Core::arrayRead($canonicalPath, $this->defaultCfg, $default);
    }

    /**
     * Returns config value, if it's not set uses the default one; returns
     * $default if the path isn't set and doesn't contain a default value
     *
     * @param string $path    Path
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function getValue($path, $default = null)
    {
        $v = Core::arrayRead($path, $_SESSION[$this->id], null);
        if ($v !== null) {
            return $v;
        }

        $path = $this->getCanonicalPath($path);

        return $this->getDefault($path, $default);
    }

    /**
     * Returns canonical path
     *
     * @param string $path Path
     *
     * @return string
     */
    public function getCanonicalPath($path)
    {
        return preg_replace('#^Servers/([\d]+)/#', 'Servers/1/', $path);
    }

    /**
     * Returns config database entry for $path
     *
     * @param string $path    path of the variable in config db
     * @param mixed  $default default value
     *
     * @return mixed
     */
    public function getDbEntry($path, $default = null)
    {
        return Core::arrayRead($path, $this->cfgDb, $default);
    }

    /**
     * Returns server count
     *
     * @return int
     */
    public function getServerCount()
    {
        return isset($_SESSION[$this->id]['Servers'])
            ? count($_SESSION[$this->id]['Servers'])
            : 0;
    }

    /**
     * Returns server list
     *
     * @return array
     */
    public function getServers(): array
    {
        return $_SESSION[$this->id]['Servers'] ?? [];
    }

    /**
     * Returns DSN of given server
     *
     * @param int $server server index
     *
     * @return string
     */
    public function getServerDSN($server)
    {
        if (! isset($_SESSION[$this->id]['Servers'][$server])) {
            return '';
        }

        $path = 'Servers/' . $server;
        $dsn = 'mysqli://';
        if ($this->getValue($path . '/auth_type') === 'config') {
            $dsn .= $this->getValue($path . '/user');
            if (! empty($this->getValue($path . '/password'))) {
                $dsn .= ':***';
            }

            $dsn .= '@';
        }

        if ($this->getValue($path . '/host') !== 'localhost') {
            $dsn .= $this->getValue($path . '/host');
            $port = $this->getValue($path . '/port');
            if ($port) {
                $dsn .= ':' . $port;
            }
        } else {
            $dsn .= $this->getValue($path . '/socket');
        }

        return $dsn;
    }

    /**
     * Returns server name
     *
     * @param int $id server index
     *
     * @return string
     */
    public function getServerName($id)
    {
        if (! isset($_SESSION[$this->id]['Servers'][$id])) {
            return '';
        }

        $verbose = $this->get('Servers/' . $id . '/verbose');
        if (! empty($verbose)) {
            return $verbose;
        }

        $host = $this->get('Servers/' . $id . '/host');

        return empty($host) ? 'localhost' : $host;
    }

    /**
     * Removes server
     *
     * @param int $server server index
     */
    public function removeServer($server): void
    {
        if (! isset($_SESSION[$this->id]['Servers'][$server])) {
            return;
        }

        $lastServer = $this->getServerCount();

        for ($i = $server; $i < $lastServer; $i++) {
            $_SESSION[$this->id]['Servers'][$i] = $_SESSION[$this->id]['Servers'][$i + 1];
        }

        unset($_SESSION[$this->id]['Servers'][$lastServer]);

        if (! isset($_SESSION[$this->id]['ServerDefault']) || $_SESSION[$this->id]['ServerDefault'] != $lastServer) {
            return;
        }

        unset($_SESSION[$this->id]['ServerDefault']);
    }

    /**
     * Returns configuration array (full, multidimensional format)
     *
     * @return array
     */
    public function getConfig()
    {
        $c = $_SESSION[$this->id];
        foreach ($this->cfgUpdateReadMapping as $mapTo => $mapFrom) {
            // if the key $c exists in $map_to
            if (Core::arrayRead($mapTo, $c) === null) {
                continue;
            }

            Core::arrayWrite($mapTo, $c, Core::arrayRead($mapFrom, $c));
            Core::arrayRemove($mapFrom, $c);
        }

        return $c;
    }

    /**
     * Returns configuration array (flat format)
     *
     * @return array
     */
    public function getConfigArray()
    {
        $c = $this->getFlatArray($_SESSION[$this->id]);

        $persistKeys = array_diff(
            array_keys($this->persistKeys),
            array_keys($c)
        );
        foreach ($persistKeys as $k) {
            $c[$k] = $this->getDefault($this->getCanonicalPath($k));
        }

        foreach ($this->cfgUpdateReadMapping as $mapTo => $mapFrom) {
            if (! isset($c[$mapFrom])) {
                continue;
            }

            $c[$mapTo] = $c[$mapFrom];
            unset($c[$mapFrom]);
        }

        return $c;
    }
}
