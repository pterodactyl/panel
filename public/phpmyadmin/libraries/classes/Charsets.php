<?php
/**
 * MySQL charset metadata and manipulations
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;

use function __;
use function array_keys;
use function count;
use function explode;
use function is_string;
use function ksort;

use const SORT_STRING;

/**
 * Class used to manage MySQL charsets
 */
class Charsets
{
    /**
     * MySQL charsets map
     *
     * @var array<string, string>
     */
    public static $mysqlCharsetMap = [
        'big5' => 'big5',
        'cp-866' => 'cp866',
        'euc-jp' => 'ujis',
        'euc-kr' => 'euckr',
        'gb2312' => 'gb2312',
        'gbk' => 'gbk',
        'iso-8859-1' => 'latin1',
        'iso-8859-2' => 'latin2',
        'iso-8859-7' => 'greek',
        'iso-8859-8' => 'hebrew',
        'iso-8859-8-i' => 'hebrew',
        'iso-8859-9' => 'latin5',
        'iso-8859-13' => 'latin7',
        'iso-8859-15' => 'latin1',
        'koi8-r' => 'koi8r',
        'shift_jis' => 'sjis',
        'tis-620' => 'tis620',
        'utf-8' => 'utf8',
        'windows-1250' => 'cp1250',
        'windows-1251' => 'cp1251',
        'windows-1252' => 'latin1',
        'windows-1256' => 'cp1256',
        'windows-1257' => 'cp1257',
    ];

    /**
     * The charset for the server
     *
     * @var Charset|null
     */
    private static $serverCharset = null;

    /** @var array<string, Charset> */
    private static $charsets = [];

    /** @var array<string, array<string, Collation>> */
    private static $collations = [];

    /**
     * Loads charset data from the server
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     */
    private static function loadCharsets(DatabaseInterface $dbi, bool $disableIs): void
    {
        /* Data already loaded */
        if (count(self::$charsets) > 0) {
            return;
        }

        $sql = 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
            . ' `DEFAULT_COLLATE_NAME` AS `Default collation`,'
            . ' `DESCRIPTION` AS `Description`,'
            . ' `MAXLEN` AS `Maxlen`'
            . ' FROM `information_schema`.`CHARACTER_SETS`';

        if ($disableIs) {
            $sql = 'SHOW CHARACTER SET';
        }

        $res = $dbi->query($sql);

        self::$charsets = [];
        foreach ($res as $row) {
            self::$charsets[$row['Charset']] = Charset::fromServer($row);
        }

        ksort(self::$charsets, SORT_STRING);
    }

    /**
     * Loads collation data from the server
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     */
    private static function loadCollations(DatabaseInterface $dbi, bool $disableIs): void
    {
        /* Data already loaded */
        if (count(self::$collations) > 0) {
            return;
        }

        if ($dbi->isMariaDB() && $dbi->getVersion() >= 101000) {
            /* Use query to accommodate new structure of MariaDB collations.
            Note, that SHOW COLLATION command is not applicable at the time of writing.
            Refer https://jira.mariadb.org/browse/MDEV-27009 */
            $sql = 'SELECT `collapp`.`FULL_COLLATION_NAME` AS `Collation`,'
                . ' `collapp`.`CHARACTER_SET_NAME` AS `Charset`,'
                . ' `collapp`.`ID` AS `Id`,'
                . ' `collapp`.`IS_DEFAULT` AS `Default`,'
                . ' `coll`.`IS_COMPILED` AS `Compiled`,'
                . ' `coll`.`SORTLEN` AS `Sortlen`'
                . ' FROM `information_schema`.`COLLATION_CHARACTER_SET_APPLICABILITY` `collapp`'
                . ' LEFT JOIN `information_schema`.`COLLATIONS` `coll`'
                . ' ON `collapp`.`COLLATION_NAME`=`coll`.`COLLATION_NAME`';
        } else {
            $sql = 'SELECT `COLLATION_NAME` AS `Collation`,'
                . ' `CHARACTER_SET_NAME` AS `Charset`,'
                . ' `ID` AS `Id`,'
                . ' `IS_DEFAULT` AS `Default`,'
                . ' `IS_COMPILED` AS `Compiled`,'
                . ' `SORTLEN` AS `Sortlen`'
                . ' FROM `information_schema`.`COLLATIONS`';

            if ($disableIs) {
                $sql = 'SHOW COLLATION';
            }
        }

        $res = $dbi->query($sql);

        self::$collations = [];
        foreach ($res as $row) {
            self::$collations[$row['Charset']][$row['Collation']] = Collation::fromServer($row);
        }

        foreach (array_keys(self::$collations) as $charset) {
            ksort(self::$collations[$charset], SORT_STRING);
        }
    }

    /**
     * Get current server charset
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     */
    public static function getServerCharset(DatabaseInterface $dbi, bool $disableIs): Charset
    {
        if (self::$serverCharset !== null) {
            return self::$serverCharset;
        }

        self::loadCharsets($dbi, $disableIs);
        $serverCharset = $dbi->getVariable('character_set_server');
        if (! is_string($serverCharset)) {// MySQL 5.7.8 fallback, issue #15614
            $serverCharset = $dbi->fetchValue('SELECT @@character_set_server;');
        }

        self::$serverCharset = self::$charsets[$serverCharset] ?? null;

        // MySQL 8.0.11+ fallback, issue #16931
        if (self::$serverCharset === null && $serverCharset === 'utf8mb3') {
            // See: https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-11.html#mysqld-8-0-11-charset
            // The utf8mb3 character set will be replaced by utf8mb4 in a future MySQL version.
            // The utf8 character set is currently an alias for utf8mb3,
            // but will at that point become a reference to utf8mb4.
            // To avoid ambiguity about the meaning of utf8,
            // consider specifying utf8mb4 explicitly for character set references instead of utf8.
            // Warning: #3719 'utf8' is currently an alias for the character set UTF8MB3 [...]
            return self::$charsets['utf8'];
        }

        if (self::$serverCharset === null) {// Fallback in case nothing is found
            return Charset::fromServer(
                [
                    'Charset' => __('Unknown'),
                    'Description' => __('Unknown'),
                ]
            );
        }

        return self::$serverCharset;
    }

    /**
     * Get all server charsets
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array<string, Charset>
     */
    public static function getCharsets(DatabaseInterface $dbi, bool $disableIs): array
    {
        self::loadCharsets($dbi, $disableIs);

        return self::$charsets;
    }

    /**
     * Get all server collations
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array<string, array<string, Collation>>
     */
    public static function getCollations(DatabaseInterface $dbi, bool $disableIs): array
    {
        self::loadCollations($dbi, $disableIs);

        return self::$collations;
    }

    /**
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param bool              $disableIs Disable use of INFORMATION_SCHEMA
     * @param string|null       $name      Collation name
     */
    public static function findCollationByName(DatabaseInterface $dbi, bool $disableIs, ?string $name): ?Collation
    {
        $charset = explode('_', $name ?? '')[0];
        $collations = self::getCollations($dbi, $disableIs);

        return $collations[$charset][$name] ?? null;
    }
}
