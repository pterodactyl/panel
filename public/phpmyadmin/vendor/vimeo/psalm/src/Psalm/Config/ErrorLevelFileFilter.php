<?php

namespace Psalm\Config;

use Psalm\Config;
use Psalm\Exception\ConfigException;
use SimpleXMLElement;

use function in_array;

class ErrorLevelFileFilter extends FileFilter
{
    /**
     * @var string
     */
    private $error_level = '';

    /**
     * @return static
     */
    public static function loadFromArray(
        array $config,
        string $base_dir,
        bool $inclusive
    ): ErrorLevelFileFilter {
        $filter = parent::loadFromArray($config, $base_dir, $inclusive);

        if (isset($config['type'])) {
            $filter->error_level = (string) $config['type'];

            if (!in_array($filter->error_level, Config::$ERROR_LEVELS, true)) {
                throw new ConfigException('Unexpected error level ' . $filter->error_level);
            }
        } else {
            throw new ConfigException('<type> element expects a level');
        }

        return $filter;
    }

    /**
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        string $base_dir,
        bool $inclusive
    ): ErrorLevelFileFilter {
        $filter = parent::loadFromXMLElement($e, $base_dir, $inclusive);

        if (isset($e['type'])) {
            $filter->error_level = (string) $e['type'];

            if (!in_array($filter->error_level, Config::$ERROR_LEVELS, true)) {
                throw new ConfigException('Unexpected error level ' . $filter->error_level);
            }
        } else {
            throw new ConfigException('<type> element expects a level');
        }

        return $filter;
    }

    public function getErrorLevel(): string
    {
        return $this->error_level;
    }
}
