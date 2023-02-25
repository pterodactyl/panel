<?php

$required_extentions = ['openssl', 'gd', 'mysql', 'PDO', 'mbstring', 'tokenizer', 'bcmath', 'xml', 'curl', 'zip', 'intl'];

$requirements = [
    'minPhp' => '8.1',
    'maxPhp' => '8.2', // This version is not supported
    'mysql' => '5.7.22',
];

function checkPhpVersion()
{
    global $requirements;
    if (version_compare(phpversion(), $requirements['minPhp'], '>=') && version_compare(phpversion(), $requirements['maxPhp'], '<=')) {
        return 'OK';
    }

    return 'not OK';
}
function checkWriteable()
{
    return is_writable('../../.env');
}
function checkHTTPS()
{
    return (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}

function getMySQLVersion()
{
    global $requirements;

    $output = shell_exec('mysql -V');
    preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);

    $versionoutput = $version[0] ?? '0';

    return intval($versionoutput) > intval($requirements['mysql']) ? 'OK' : $versionoutput;
}

function getZipVersion()
{
    $output = shell_exec('zip  -v');
    preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);

    $versionoutput = $version[0] ?? 0;

    return $versionoutput != 0 ? 'OK' : 'not OK';
}

function getGitVersion()
{
    $output = shell_exec('git  --version');
    preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);

    $versionoutput = $version[0] ?? 0;

    return $versionoutput != 0 ? 'OK' : 'not OK';
}

function getTarVersion()
{
    $output = shell_exec('tar  --version');
    preg_match('@[0-9]+\.[0-9]+@', $output, $version);

    $versionoutput = $version[0] ?? 0;

    return $versionoutput != 0 ? 'OK' : 'not OK';
}

function checkExtensions()
{
    global $required_extentions;

    $not_ok = [];
    $extentions = get_loaded_extensions();

    foreach ($required_extentions as $ext) {
        if (! preg_grep('/^(?=.*'.$ext.').*$/', $extentions)) {
            array_push($not_ok, $ext);
        }
    }

    return $not_ok;
}

function setEnvironmentValue($envKey, $envValue)
{
    $envFile = dirname(__FILE__, 3).'/.env';
    $str = file_get_contents($envFile);

    $str .= "\n"; // In case the searched variable is in the last line without \n
    $keyPosition = strpos($str, "{$envKey}=");
    $endOfLinePosition = strpos($str, PHP_EOL, $keyPosition);
    $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
    $str = substr($str, 0, -1);

    $fp = fopen($envFile, 'w');
    fwrite($fp, $str);
    fclose($fp);
}

function getEnvironmentValue($envKey)
{
    $envFile = dirname(__FILE__, 3).'/.env';
    $str = file_get_contents($envFile);

    $str .= "\n"; // In case the searched variable is in the last line without \n
    $keyPosition = strpos($str, "{$envKey}=");
    $endOfLinePosition = strpos($str, PHP_EOL, $keyPosition);
    $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
    $value = substr($oldLine, strpos($oldLine, '=') + 1);

    return $value;
}

function run_console($command)
{
    $path = dirname(__FILE__, 3);
    $cmd = "cd '$path' && bash -c 'exec -a ServerCPP $command' 2>&1";

    return shell_exec($cmd);
}

function wh_log($log_msg)
{
    $log_filename = 'logs';
    if (! file_exists($log_filename)) {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/installer.log';
    // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
    file_put_contents($log_file_data, '['.date('h:i:s').'] '.$log_msg."\n", FILE_APPEND);
}

function generateRandomString($length = 8)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}
