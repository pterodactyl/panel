<?php
/**
 * Form validation for configuration editor
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Core;
use PhpMyAdmin\Util;

use function __;
use function array_map;
use function array_merge;
use function array_shift;
use function call_user_func_array;
use function count;
use function error_clear_last;
use function error_get_last;
use function explode;
use function filter_var;
use function htmlspecialchars;
use function intval;
use function is_array;
use function is_object;
use function mb_strpos;
use function mb_substr;
use function mysqli_close;
use function mysqli_connect;
use function mysqli_report;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function trim;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const MYSQLI_REPORT_OFF;
use const PHP_INT_MAX;

/**
 * Validation class for various validation functions
 *
 * Validation function takes two argument: id for which it is called
 * and array of fields' values (usually values for entire formset).
 * The function must always return an array with an error (or error array)
 * assigned to a form element (formset name or field path). Even if there are
 * no errors, key must be set with an empty value.
 *
 * Validation functions are assigned in $cfg_db['_validators'] (config.values.php).
 */
class Validator
{
    /**
     * Returns validator list
     *
     * @param ConfigFile $cf Config file instance
     *
     * @return array
     */
    public static function getValidators(ConfigFile $cf)
    {
        static $validators = null;

        if ($validators !== null) {
            return $validators;
        }

        $validators = $cf->getDbEntry('_validators', []);
        if ($GLOBALS['config']->get('is_setup')) {
            return $validators;
        }

        // not in setup script: load additional validators for user
        // preferences we need original config values not overwritten
        // by user preferences, creating a new PhpMyAdmin\Config instance is a
        // better idea than hacking into its code
        $uvs = $cf->getDbEntry('_userValidators', []);
        foreach ($uvs as $field => $uvList) {
            $uvList = (array) $uvList;
            foreach ($uvList as &$uv) {
                if (! is_array($uv)) {
                    continue;
                }

                for ($i = 1, $nb = count($uv); $i < $nb; $i++) {
                    if (mb_substr($uv[$i], 0, 6) !== 'value:') {
                        continue;
                    }

                    $uv[$i] = Core::arrayRead(
                        mb_substr($uv[$i], 6),
                        $GLOBALS['config']->baseSettings
                    );
                }
            }

            $validators[$field] = isset($validators[$field])
                ? array_merge((array) $validators[$field], $uvList)
                : $uvList;
        }

        return $validators;
    }

    /**
     * Runs validation $validator_id on values $values and returns error list.
     *
     * Return values:
     * o array, keys - field path or formset id, values - array of errors
     *   when $isPostSource is true values is an empty array to allow for error list
     *   cleanup in HTML document
     * o false - when no validators match name(s) given by $validator_id
     *
     * @param ConfigFile   $cf           Config file instance
     * @param string|array $validatorId  ID of validator(s) to run
     * @param array        $values       Values to validate
     * @param bool         $isPostSource tells whether $values are directly from
     *                                   POST request
     *
     * @return bool|array
     */
    public static function validate(
        ConfigFile $cf,
        $validatorId,
        array &$values,
        $isPostSource
    ) {
        // find validators
        $validatorId = (array) $validatorId;
        $validators = static::getValidators($cf);
        $vids = [];
        foreach ($validatorId as &$vid) {
            $vid = $cf->getCanonicalPath($vid);
            if (! isset($validators[$vid])) {
                continue;
            }

            $vids[] = $vid;
        }

        if (empty($vids)) {
            return false;
        }

        // create argument list with canonical paths and remember path mapping
        $arguments = [];
        $keyMap = [];
        foreach ($values as $k => $v) {
            $k2 = $isPostSource ? str_replace('-', '/', $k) : $k;
            $k2 = mb_strpos($k2, '/')
                ? $cf->getCanonicalPath($k2)
                : $k2;
            $keyMap[$k2] = $k;
            $arguments[$k2] = $v;
        }

        // validate
        $result = [];
        foreach ($vids as $vid) {
            // call appropriate validation functions
            foreach ((array) $validators[$vid] as $validator) {
                $vdef = (array) $validator;
                $vname = array_shift($vdef);
                /** @var callable $vname */
                $vname = 'PhpMyAdmin\Config\Validator::' . $vname;
                $args = array_merge([$vid, &$arguments], $vdef);
                $r = call_user_func_array($vname, $args);

                // merge results
                if (! is_array($r)) {
                    continue;
                }

                foreach ($r as $key => $errorList) {
                    // skip empty values if $isPostSource is false
                    if (! $isPostSource && empty($errorList)) {
                        continue;
                    }

                    if (! isset($result[$key])) {
                        $result[$key] = [];
                    }

                    $errorList = array_map('PhpMyAdmin\Sanitize::sanitizeMessage', (array) $errorList);
                    $result[$key] = array_merge($result[$key], $errorList);
                }
            }
        }

        // restore original paths
        $newResult = [];
        foreach ($result as $k => $v) {
            $k2 = $keyMap[$k] ?? $k;
            $newResult[$k2] = $v;
        }

        return empty($newResult) ? true : $newResult;
    }

    /**
     * Test database connection
     *
     * @param string $host     host name
     * @param string $port     tcp port to use
     * @param string $socket   socket to use
     * @param string $user     username to use
     * @param string $pass     password to use
     * @param string $errorKey key to use in return array
     *
     * @return bool|array
     */
    public static function testDBConnection(
        $host,
        $port,
        $socket,
        $user,
        $pass = null,
        $errorKey = 'Server'
    ) {
        if ($GLOBALS['cfg']['DBG']['demo']) {
            // Connection test disabled on the demo server!
            return true;
        }

        $error = null;
        $host = Core::sanitizeMySQLHost($host);

        error_clear_last();

        /** @var string $socket */
        $socket = empty($socket) ? null : $socket;
        /** @var int $port */
        $port = empty($port) ? null : (int) $port;

        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = @mysqli_connect($host, $user, (string) $pass, '', $port, $socket);
        if (! $conn) {
            $error = __('Could not connect to the database server!');
        } else {
            mysqli_close($conn);
        }

        if ($error !== null) {
            $lastError = error_get_last();
            if ($lastError !== null) {
                $error .= ' - ' . $lastError['message'];
            }
        }

        return $error === null ? true : [$errorKey => $error];
    }

    /**
     * Validate server config
     *
     * @param string $path   path to config, not used
     *                       keep this parameter since the method is invoked using
     *                       reflection along with other similar methods
     * @param array  $values config values
     *
     * @return array
     */
    public static function validateServer($path, array $values)
    {
        $result = [
            'Server' => '',
            'Servers/1/user' => '',
            'Servers/1/SignonSession' => '',
            'Servers/1/SignonURL' => '',
        ];
        $error = false;
        if (empty($values['Servers/1/auth_type'])) {
            $values['Servers/1/auth_type'] = '';
            $result['Servers/1/auth_type'] = __('Invalid authentication type!');
            $error = true;
        }

        if ($values['Servers/1/auth_type'] === 'config' && empty($values['Servers/1/user'])) {
            $result['Servers/1/user'] = __('Empty username while using [kbd]config[/kbd] authentication method!');
            $error = true;
        }

        if ($values['Servers/1/auth_type'] === 'signon' && empty($values['Servers/1/SignonSession'])) {
            $result['Servers/1/SignonSession'] = __(
                'Empty signon session name while using [kbd]signon[/kbd] authentication method!'
            );
            $error = true;
        }

        if ($values['Servers/1/auth_type'] === 'signon' && empty($values['Servers/1/SignonURL'])) {
            $result['Servers/1/SignonURL'] = __(
                'Empty signon URL while using [kbd]signon[/kbd] authentication method!'
            );
            $error = true;
        }

        if (! $error && $values['Servers/1/auth_type'] === 'config') {
            $password = '';
            if (! empty($values['Servers/1/password'])) {
                $password = $values['Servers/1/password'];
            }

            $test = static::testDBConnection(
                empty($values['Servers/1/host']) ? '' : $values['Servers/1/host'],
                empty($values['Servers/1/port']) ? '' : $values['Servers/1/port'],
                empty($values['Servers/1/socket']) ? '' : $values['Servers/1/socket'],
                empty($values['Servers/1/user']) ? '' : $values['Servers/1/user'],
                $password,
                'Server'
            );

            if (is_array($test)) {
                $result = array_merge($result, $test);
            }
        }

        return $result;
    }

    /**
     * Validate pmadb config
     *
     * @param string $path   path to config, not used
     *                       keep this parameter since the method is invoked using
     *                       reflection along with other similar methods
     * @param array  $values config values
     *
     * @return array
     */
    public static function validatePMAStorage($path, array $values)
    {
        $result = [
            'Server_pmadb' => '',
            'Servers/1/controluser' => '',
            'Servers/1/controlpass' => '',
        ];
        $error = false;

        if (empty($values['Servers/1/pmadb'])) {
            return $result;
        }

        $result = [];
        if (empty($values['Servers/1/controluser'])) {
            $result['Servers/1/controluser'] = __(
                'Empty phpMyAdmin control user while using phpMyAdmin configuration storage!'
            );
            $error = true;
        }

        if (empty($values['Servers/1/controlpass'])) {
            $result['Servers/1/controlpass'] = __(
                'Empty phpMyAdmin control user password while using phpMyAdmin configuration storage!'
            );
            $error = true;
        }

        if (! $error) {
            $test = static::testDBConnection(
                empty($values['Servers/1/host']) ? '' : $values['Servers/1/host'],
                empty($values['Servers/1/port']) ? '' : $values['Servers/1/port'],
                empty($values['Servers/1/socket']) ? '' : $values['Servers/1/socket'],
                empty($values['Servers/1/controluser']) ? '' : $values['Servers/1/controluser'],
                empty($values['Servers/1/controlpass']) ? '' : $values['Servers/1/controlpass'],
                'Server_pmadb'
            );
            if (is_array($test)) {
                $result = array_merge($result, $test);
            }
        }

        return $result;
    }

    /**
     * Validates regular expression
     *
     * @param string $path   path to config
     * @param array  $values config values
     *
     * @return array
     */
    public static function validateRegex($path, array $values)
    {
        $result = [$path => ''];

        if (empty($values[$path])) {
            return $result;
        }

        error_clear_last();

        $matches = [];
        // in libraries/ListDatabase.php _checkHideDatabase(),
        // a '/' is used as the delimiter for hide_db
        @preg_match('/' . Util::requestString($values[$path]) . '/', '', $matches);

        $currentError = error_get_last();

        if ($currentError !== null) {
            $error = preg_replace('/^preg_match\(\): /', '', $currentError['message']);

            return [$path => $error];
        }

        return $result;
    }

    /**
     * Validates TrustedProxies field
     *
     * @param string $path   path to config
     * @param array  $values config values
     *
     * @return array
     */
    public static function validateTrustedProxies($path, array $values)
    {
        $result = [$path => []];

        if (empty($values[$path])) {
            return $result;
        }

        if (is_array($values[$path]) || is_object($values[$path])) {
            // value already processed by FormDisplay::save
            $lines = [];
            foreach ($values[$path] as $ip => $v) {
                $v = Util::requestString($v);
                $lines[] = preg_match('/^-\d+$/', $ip)
                    ? $v
                    : $ip . ': ' . $v;
            }
        } else {
            // AJAX validation
            $lines = explode("\n", $values[$path]);
        }

        foreach ($lines as $line) {
            $line = trim($line);
            $matches = [];
            // we catch anything that may (or may not) be an IP
            if (! preg_match('/^(.+):(?:[ ]?)\\w+$/', $line, $matches)) {
                $result[$path][] = __('Incorrect value:') . ' '
                    . htmlspecialchars($line);
                continue;
            }

            // now let's check whether we really have an IP address
            if (
                filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
                && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false
            ) {
                $ip = htmlspecialchars(trim($matches[1]));
                $result[$path][] = sprintf(__('Incorrect IP address: %s'), $ip);
                continue;
            }
        }

        return $result;
    }

    /**
     * Tests integer value
     *
     * @param string $path          path to config
     * @param array  $values        config values
     * @param bool   $allowNegative allow negative values
     * @param bool   $allowZero     allow zero
     * @param int    $maxValue      max allowed value
     * @param string $errorString   error message string
     *
     * @return string  empty string if test is successful
     */
    public static function validateNumber(
        $path,
        array $values,
        $allowNegative,
        $allowZero,
        $maxValue,
        $errorString
    ) {
        if (empty($values[$path])) {
            return '';
        }

        $value = Util::requestString($values[$path]);

        if (
            intval($value) != $value
            || (! $allowNegative && $value < 0)
            || (! $allowZero && $value == 0)
            || $value > $maxValue
        ) {
            return $errorString;
        }

        return '';
    }

    /**
     * Validates port number
     *
     * @param string $path   path to config
     * @param array  $values config values
     *
     * @return array
     */
    public static function validatePortNumber($path, array $values)
    {
        return [
            $path => static::validateNumber(
                $path,
                $values,
                false,
                false,
                65535,
                __('Not a valid port number!')
            ),
        ];
    }

    /**
     * Validates positive number
     *
     * @param string $path   path to config
     * @param array  $values config values
     *
     * @return array
     */
    public static function validatePositiveNumber($path, array $values)
    {
        return [
            $path => static::validateNumber(
                $path,
                $values,
                false,
                false,
                PHP_INT_MAX,
                __('Not a positive number!')
            ),
        ];
    }

    /**
     * Validates non-negative number
     *
     * @param string $path   path to config
     * @param array  $values config values
     *
     * @return array
     */
    public static function validateNonNegativeNumber($path, array $values)
    {
        return [
            $path => static::validateNumber(
                $path,
                $values,
                false,
                true,
                PHP_INT_MAX,
                __('Not a non-negative number!')
            ),
        ];
    }

    /**
     * Validates value according to given regular expression
     * Pattern and modifiers must be a valid for PCRE <b>and</b> JavaScript RegExp
     *
     * @param string $path   path to config
     * @param array  $values config values
     * @param string $regex  regular expression to match
     *
     * @return array|string
     */
    public static function validateByRegex($path, array $values, $regex)
    {
        if (! isset($values[$path])) {
            return '';
        }

        $result = preg_match($regex, Util::requestString($values[$path]));

        return [$path => $result ? '' : __('Incorrect value!')];
    }

    /**
     * Validates upper bound for numeric inputs
     *
     * @param string $path     path to config
     * @param array  $values   config values
     * @param int    $maxValue maximal allowed value
     *
     * @return array
     */
    public static function validateUpperBound($path, array $values, $maxValue)
    {
        $result = $values[$path] <= $maxValue;

        return [
            $path => $result ? '' : sprintf(
                __('Value must be less than or equal to %s!'),
                $maxValue
            ),
        ];
    }
}
