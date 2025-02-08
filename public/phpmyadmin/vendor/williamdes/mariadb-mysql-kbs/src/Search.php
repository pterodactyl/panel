<?php

declare(strict_types = 1);

namespace Williamdes\MariaDBMySQLKBS;

use stdClass;

class Search
{
    /**
     * Loaded data
     *
     * @var stdClass
     */
    public static $data;

    /**
     * Data is loaded
     *
     * @var bool
     */
    public static $loaded = false;

    public const ANY          = -1;
    public const MYSQL        = 1;
    public const MARIADB      = 2;
    public const AURORA_MYSQL = 3;
    public const DS           = DIRECTORY_SEPARATOR;

    /**
     * The directory where the data is located
     *
     * @var string
     */
    public static $DATA_DIR = __DIR__ . self::DS . '..' . self::DS . 'dist' . self::DS;

    /**
     * Load data from disk
     *
     * @return void
     * @throws KBException
     */
    public static function loadData(): void
    {
        if (Search::$loaded === false) {
            $filePath = Search::$DATA_DIR . 'merged-ultraslim.json';
            if (! is_file($filePath)) {
                throw new KBException($filePath . ' does not exist !');
            }
            $contents = file_get_contents($filePath);
            if ($contents === false) {
                throw new KBException($filePath . ' does not exist !');
            }
            $decodedData = json_decode($contents);
            if ($decodedData instanceof stdClass) {
                Search::$data   = $decodedData;
                Search::$loaded = true;
                return;
            }
            throw new KBException($filePath . ' could not be JSON decoded !');
        }
    }

    /**
     * Load test data
     *
     * @param SlimData $slimData The SlimData object
     * @return void
     */
    public static function loadTestData(SlimData $slimData): void
    {
        $decodedData = json_decode((string) json_encode($slimData));
        if ($decodedData instanceof stdClass) {
            Search::$data   = $decodedData;
            Search::$loaded = true;
        }
    }

    /**
     * get the first link to doc available
     *
     * @param string $name Name of variable
     * @param int    $type (optional) Type of link Search::MYSQL/Search::MARIADB/Search::ANY
     * @return string
     * @throws KBException
     */
    public static function getByName(string $name, int $type = Search::ANY): string
    {
        self::loadData();
        $kbEntries = self::getVariable($name);
        if (isset($kbEntries->a)) {
            foreach ($kbEntries->a as $kbEntry) {
                $urlEnd = isset($kbEntry->a) ? '#' . $kbEntry->a : '';

                if ($type === Search::ANY) {
                    return Search::$data->urls[$kbEntry->u] . $urlEnd;
                } elseif ($type === Search::MYSQL) {
                    if ($kbEntry->t === Search::MYSQL) {
                        return Search::$data->urls[$kbEntry->u] . $urlEnd;
                    }
                    if ($kbEntry->t === Search::AURORA_MYSQL) {
                        return Search::$data->urls[$kbEntry->u] . $urlEnd;
                    }
                } elseif ($type === Search::MARIADB) {
                    if ($kbEntry->t === Search::MARIADB) {
                        return Search::$data->urls[$kbEntry->u] . $urlEnd;
                    }
                } elseif ($type === Search::AURORA_MYSQL) {
                    if ($kbEntry->t === Search::AURORA_MYSQL) {
                        return Search::$data->urls[$kbEntry->u] . $urlEnd;
                    }
                }
            }
        }

        throw new KBException($name . ' does not exist for this type of documentation !');
    }

    /**
     * Get a variable
     *
     * @param string $name Name of variable
     * @return stdClass
     * @throws KBException
     */
    public static function getVariable(string $name): stdClass
    {
        self::loadData();
        if (isset(Search::$data->vars->{$name})) {
            return Search::$data->vars->{$name};
        } else {
            throw new KBException($name . ' does not exist !');
        }
    }

    /**
     * get the type of the variable
     *
     * @param string $name Name of variable
     * @return string
     * @throws KBException
     */
    public static function getVariableType(string $name): string
    {
        self::loadData();
        $kbEntry = self::getVariable($name);
        if (isset($kbEntry->t)) {
            return Search::$data->varTypes->{$kbEntry->t};
        } else {
            throw new KBException($name . ' does have a known type !');
        }
    }

    /**
     * Return the list of static variables
     *
     * @return array<int,string>
     */
    public static function getStaticVariables(): array
    {
        return self::getVariablesWithDynamic(false);
    }

    /**
     * Return the list of dynamic variables
     *
     * @return array<int,string>
     */
    public static function getDynamicVariables(): array
    {
        return self::getVariablesWithDynamic(true);
    }

    /**
     * Return the list of variables having dynamic = $dynamic
     *
     * @param bool $dynamic dynamic=true/dynamic=false
     * @return array<int,string>
     */
    public static function getVariablesWithDynamic(bool $dynamic): array
    {
        self::loadData();
        $staticVars = [];
        foreach (Search::$data->vars as $name => $var) {
            if (isset($var->d)) {
                if ($var->d === $dynamic) {
                    $staticVars[] = $name;
                }
            }
        }
        return $staticVars;
    }

}
