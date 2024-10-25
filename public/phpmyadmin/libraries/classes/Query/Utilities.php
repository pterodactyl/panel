<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Error;
use PhpMyAdmin\Url;

use function __;
use function array_slice;
use function debug_backtrace;
use function explode;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function intval;
use function md5;
use function sprintf;
use function str_contains;
use function strcasecmp;
use function strnatcasecmp;
use function strtolower;

/**
 * Some helfull functions for common tasks related to SQL results
 */
class Utilities
{
    /**
     * Get the list of system schemas
     *
     * @return string[] list of system schemas
     */
    public static function getSystemSchemas(): array
    {
        $schemas = [
            'information_schema',
            'performance_schema',
            'mysql',
            'sys',
        ];
        $systemSchemas = [];
        foreach ($schemas as $schema) {
            if (! self::isSystemSchema($schema, true)) {
                continue;
            }

            $systemSchemas[] = $schema;
        }

        return $systemSchemas;
    }

    /**
     * Checks whether given schema is a system schema
     *
     * @param string $schema_name        Name of schema (database) to test
     * @param bool   $testForMysqlSchema Whether 'mysql' schema should
     *                                   be treated the same as IS and DD
     */
    public static function isSystemSchema(
        string $schema_name,
        bool $testForMysqlSchema = false
    ): bool {
        $schema_name = strtolower($schema_name);

        $isMySqlSystemSchema = $schema_name === 'mysql' && $testForMysqlSchema;

        return $schema_name === 'information_schema'
            || $schema_name === 'performance_schema'
            || $isMySqlSystemSchema
            || $schema_name === 'sys';
    }

    /**
     * Formats database error message in a friendly way.
     * This is needed because some errors messages cannot
     * be obtained by mysql_error().
     *
     * @param int    $error_number  Error code
     * @param string $error_message Error message as returned by server
     *
     * @return string HML text with error details
     * @psalm-return non-empty-string
     */
    public static function formatError(int $error_number, string $error_message): string
    {
        $error_message = htmlspecialchars($error_message);

        $error = '#' . ((string) $error_number);
        $separator = ' &mdash; ';

        if ($error_number == 2002) {
            $error .= ' - ' . $error_message;
            $error .= $separator;
            $error .= __('The server is not responding (or the local server\'s socket is not correctly configured).');
        } elseif ($error_number == 2003) {
            $error .= ' - ' . $error_message;
            $error .= $separator . __('The server is not responding.');
        } elseif ($error_number == 1698) {
            $error .= ' - ' . $error_message;
            $error .= $separator . '<a href="' . Url::getFromRoute('/logout') . '" class="disableAjax">';
            $error .= __('Logout and try as another user.') . '</a>';
        } elseif ($error_number == 1005) {
            if (str_contains($error_message, 'errno: 13')) {
                $error .= ' - ' . $error_message;
                $error .= $separator
                    . __('Please check privileges of directory containing database.');
            } else {
                /**
                 * InnoDB constraints, see
                 * https://dev.mysql.com/doc/refman/8.0/en/create-table-foreign-keys.html
                 */
                $error .= ' - ' . $error_message .
                    ' (<a href="' .
                    Url::getFromRoute('/server/engines/InnoDB/Status') .
                    '">' . __('Details…') . '</a>)';
            }
        } else {
            $error .= ' - ' . $error_message;
        }

        return $error;
    }

    /**
     * usort comparison callback
     *
     * @param array  $a         first argument to sort
     * @param array  $b         second argument to sort
     * @param string $sortBy    Key to sort by
     * @param string $sortOrder The order (ASC/DESC)
     *
     * @return int  a value representing whether $a should be before $b in the
     *              sorted array or not
     */
    public static function usortComparisonCallback(array $a, array $b, string $sortBy, string $sortOrder): int
    {
        global $cfg;

        /* No sorting when key is not present */
        if (! isset($a[$sortBy], $b[$sortBy])) {
            return 0;
        }

        // produces f.e.:
        // return -1 * strnatcasecmp($a['SCHEMA_TABLES'], $b['SCHEMA_TABLES'])
        $compare = $cfg['NaturalOrder'] ? strnatcasecmp(
            (string) $a[$sortBy],
            (string) $b[$sortBy]
        ) : strcasecmp(
            (string) $a[$sortBy],
            (string) $b[$sortBy]
        );

        return ($sortOrder === 'ASC' ? 1 : -1) * $compare;
    }

    /**
     * Convert version string to integer.
     *
     * @param string $version MySQL server version
     */
    public static function versionToInt(string $version): int
    {
        $match = explode('.', $version);

        return (int) sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2]));
    }

    /**
     * Stores query data into session data for debugging purposes
     *
     * @param string                $query        Query text
     * @param string|null           $errorMessage Error message from getError()
     * @param ResultInterface|false $result       Query result
     * @param int|float             $time         Time to execute query
     */
    public static function debugLogQueryIntoSession(string $query, ?string $errorMessage, $result, $time): void
    {
        $dbgInfo = [];

        if ($result === false && $errorMessage !== null) {
            // because Utilities::formatError is applied in DbiMysqli
            $dbgInfo['error'] = htmlspecialchars_decode($errorMessage);
        }

        $dbgInfo['query'] = $query;
        $dbgInfo['time'] = $time;
        // Get and slightly format backtrace, this is used
        // in the javascript console.
        // Strip call to debugLogQueryIntoSession
        $dbgInfo['trace'] = Error::processBacktrace(
            array_slice(debug_backtrace(), 1)
        );
        $dbgInfo['hash'] = md5($query);

        $_SESSION['debug']['queries'][] = $dbgInfo;
    }
}
