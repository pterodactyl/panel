<?php
/**
 * Responsible for retrieving version information and notifying about latest version
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Utils\HttpRequest;
use stdClass;

use function count;
use function explode;
use function intval;
use function is_numeric;
use function is_object;
use function json_decode;
use function preg_match;
use function strlen;
use function strpos;
use function substr;
use function time;
use function version_compare;

use const PHP_VERSION;

/**
 * Responsible for retrieving version information and notifying about latest version
 */
class VersionInformation
{
    /**
     * Returns information with latest version from phpmyadmin.net
     *
     * @return stdClass|null JSON decoded object with the data
     */
    public function getLatestVersion(): ?stdClass
    {
        if (! $GLOBALS['cfg']['VersionCheck']) {
            return null;
        }

        // Get response text from phpmyadmin.net or from the session
        // Update cache every 6 hours
        if (
            isset($_SESSION['cache']['version_check'])
            && time() < $_SESSION['cache']['version_check']['timestamp'] + 3600 * 6
        ) {
            $save = false;
            $response = $_SESSION['cache']['version_check']['response'];
        } else {
            $save = true;
            $file = 'https://www.phpmyadmin.net/home_page/version.json';
            $httpRequest = new HttpRequest();
            $response = $httpRequest->create($file, 'GET');
        }

        $response = $response ?: '{}';
        /* Parse response */
        $data = json_decode($response);

        /* Basic sanity checking */
        if (! is_object($data) || empty($data->version) || empty($data->releases) || empty($data->date)) {
            return null;
        }

        if ($save) {
            $_SESSION['cache']['version_check'] = [
                'response' => $response,
                'timestamp' => time(),
            ];
        }

        return $data;
    }

    /**
     * Calculates numerical equivalent of phpMyAdmin version string
     *
     * @param string $version version
     *
     * @return mixed false on failure, integer on success
     */
    public function versionToInt($version)
    {
        $parts = explode('-', $version);
        if (count($parts) > 1) {
            $suffix = $parts[1];
        } else {
            $suffix = '';
        }

        $parts = explode('.', $parts[0]);

        $result = 0;

        if (count($parts) >= 1 && is_numeric($parts[0])) {
            $result += 1000000 * (int) $parts[0];
        }

        if (count($parts) >= 2 && is_numeric($parts[1])) {
            $result += 10000 * (int) $parts[1];
        }

        if (count($parts) >= 3 && is_numeric($parts[2])) {
            $result += 100 * (int) $parts[2];
        }

        if (count($parts) >= 4 && is_numeric($parts[3])) {
            $result += 1 * (int) $parts[3];
        }

        if (! empty($suffix)) {
            $matches = [];
            if (preg_match('/^(\D+)(\d+)$/', $suffix, $matches)) {
                $suffix = $matches[1];
                $result += intval($matches[2]);
            }

            switch ($suffix) {
                case 'pl':
                    $result += 60;
                    break;
                case 'rc':
                    $result += 30;
                    break;
                case 'beta':
                    $result += 20;
                    break;
                case 'alpha':
                    $result += 10;
                    break;
                case 'dev':
                    $result += 0;
                    break;
            }
        } else {
            $result += 50; // for final
        }

        return $result;
    }

    /**
     * Returns the version and date of the latest phpMyAdmin version compatible
     * with the available PHP and MySQL versions
     *
     * @param array $releases array of information related to each version
     *
     * @return array|null containing the version and date of latest compatible version
     */
    public function getLatestCompatibleVersion(array $releases)
    {
        // Maintains the latest compatible version
        $latestRelease = null;
        foreach ($releases as $release) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $phpVersions = $release->php_versions;
            $phpConditions = explode(',', $phpVersions);
            foreach ($phpConditions as $phpCondition) {
                if (! $this->evaluateVersionCondition('PHP', $phpCondition)) {
                    continue 2;
                }
            }

            // We evaluate MySQL version constraint if there are only
            // one server configured.
            if (count($GLOBALS['cfg']['Servers']) === 1) {
                // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                $mysqlVersions = $release->mysql_versions;
                $mysqlConditions = explode(',', $mysqlVersions);
                foreach ($mysqlConditions as $mysqlCondition) {
                    if (! $this->evaluateVersionCondition('MySQL', $mysqlCondition)) {
                        continue 2;
                    }
                }
            }

            // To compare the current release with the previous latest release or no release is set
            if ($latestRelease !== null && ! version_compare($latestRelease['version'], $release->version, '<')) {
                continue;
            }

            $latestRelease = [
                'version' => $release->version,
                'date' => $release->date,
            ];
        }

        // no compatible version
        return $latestRelease;
    }

    /**
     * Checks whether PHP or MySQL version meets supplied version condition
     *
     * @param string $type      PHP or MySQL
     * @param string $condition version condition
     *
     * @return bool whether the condition is met
     */
    public function evaluateVersionCondition(string $type, string $condition)
    {
        $operator = null;
        $version = null;
        $operators = [
            '<=',
            '>=',
            '!=',
            '<>',
            '<',
            '>',
            '=',
        ]; // preserve order
        foreach ($operators as $oneOperator) {
            if (strpos($condition, $oneOperator) === 0) {
                $operator = $oneOperator;
                $version = substr($condition, strlen($oneOperator));
                break;
            }
        }

        $myVersion = null;
        if ($type === 'PHP') {
            $myVersion = $this->getPHPVersion();
        } elseif ($type === 'MySQL') {
            $myVersion = $this->getMySQLVersion();
        }

        if ($myVersion !== null && $version !== null && $operator !== null) {
            return version_compare($myVersion, $version, $operator);
        }

        return false;
    }

    /**
     * Returns the PHP version
     *
     * @return string PHP version
     */
    protected function getPHPVersion()
    {
        return PHP_VERSION;
    }

    /**
     * Returns the MySQL version if connected to a database
     *
     * @return string|null MySQL version
     */
    protected function getMySQLVersion()
    {
        global $dbi;

        if (isset($dbi)) {
            return $dbi->getVersionString();
        }

        return null;
    }
}
