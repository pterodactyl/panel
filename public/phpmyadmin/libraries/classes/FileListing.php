<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function asort;
use function closedir;
use function file_exists;
use function function_exists;
use function is_file;
use function is_link;
use function opendir;
use function preg_match;
use function readdir;
use function substr;

/**
 * Functions for listing directories
 */
class FileListing
{
    /**
     * Returns array of filtered file names
     *
     * @param string $dir        directory to list
     * @param string $expression regular expression to match files
     *
     * @return array|bool sorted file list on success, false on failure
     */
    public function getDirContent(string $dir, string $expression = '')
    {
        if (! @file_exists($dir)) {
            return false;
        }

        $handle = @opendir($dir);

        if ($handle === false) {
            return false;
        }

        $result = [];
        if (substr($dir, -1) !== '/') {
            $dir .= '/';
        }

        while ($file = @readdir($handle)) {
            if (
                ! @is_file($dir . $file)
                || @is_link($dir . $file)
                || ($expression != '' && ! preg_match($expression, $file))
            ) {
                continue;
            }

            $result[] = $file;
        }

        closedir($handle);
        asort($result);

        return $result;
    }

    /**
     * Returns options of filtered file names
     *
     * @param string $dir        directory to list
     * @param string $extensions regular expression to match files
     * @param string $active     currently active choice
     *
     * @return string|false Html <option> field, false if not files in dir
     */
    public function getFileSelectOptions(
        string $dir,
        string $extensions = '',
        string $active = ''
    ) {
        $list = $this->getDirContent($dir, $extensions);
        if ($list === false) {
            return false;
        }

        $template = new Template();

        return $template->render('file_select_options', [
            'filesList' => $list,
            'active' => $active,
        ]);
    }

    /**
     * Get currently supported decompressions.
     *
     * @return string separated list of extensions usable in getDirContent
     */
    public function supportedDecompressions(): string
    {
        global $cfg;

        $compressions = '';

        if ($cfg['GZipDump'] && function_exists('gzopen')) {
            $compressions = 'gz';
        }

        if ($cfg['BZipDump'] && function_exists('bzopen')) {
            if (! empty($compressions)) {
                $compressions .= '|';
            }

            $compressions .= 'bz2';
        }

        if ($cfg['ZipDump'] && function_exists('gzinflate')) {
            if (! empty($compressions)) {
                $compressions .= '|';
            }

            $compressions .= 'zip';
        }

        return $compressions;
    }
}
