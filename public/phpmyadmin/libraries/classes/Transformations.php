<?php
/**
 * Set of functions used with the relation and pdf feature
 *
 * This file also provides basic functions to use in other plugins!
 * These are declared in the 'GLOBAL Plugin functions' section
 *
 * Please use short and expressive names.
 * For now, special characters which aren't allowed in
 * filenames or functions should not be used.
 *
 * Please provide a comment for your function,
 * what it does and what parameters are available.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Plugins\TransformationsInterface;

use function array_shift;
use function class_exists;
use function closedir;
use function count;
use function explode;
use function ltrim;
use function mb_strtolower;
use function mb_substr;
use function opendir;
use function preg_match;
use function preg_replace;
use function readdir;
use function rtrim;
use function sort;
use function str_contains;
use function str_replace;
use function stripslashes;
use function strlen;
use function trim;
use function ucfirst;
use function ucwords;

/**
 * Transformations class
 */
class Transformations
{
    /**
     * Returns array of options from string with options separated by comma,
     * removes quotes
     *
     * <code>
     * getOptions("'option ,, quoted',abd,'2,3',");
     * // array {
     * //     'option ,, quoted',
     * //     'abc',
     * //     '2,3',
     * //     '',
     * // }
     * </code>
     *
     * @param string $optionString comma separated options
     *
     * @return array options
     */
    public function getOptions($optionString)
    {
        if (strlen($optionString) === 0) {
            return [];
        }

        $transformOptions = explode(',', $optionString);

        $result = [];

        while (($option = array_shift($transformOptions)) !== null) {
            $trimmed = trim($option);
            if (strlen($trimmed) > 1 && $trimmed[0] == "'" && $trimmed[strlen($trimmed) - 1] == "'") {
                // '...'
                $option = mb_substr($trimmed, 1, -1);
            } elseif (isset($trimmed[0]) && $trimmed[0] == "'") {
                // '...,
                $trimmed = ltrim($option);
                $rtrimmed = '';
                while (($option = array_shift($transformOptions)) !== null) {
                    // ...,
                    $trimmed .= ',' . $option;
                    $rtrimmed = rtrim($trimmed);
                    if ($rtrimmed[strlen($rtrimmed) - 1] == "'") {
                        // ,...'
                        break;
                    }
                }

                $option = mb_substr($rtrimmed, 1, -1);
            }

            $result[] = stripslashes($option);
        }

        return $result;
    }

    /**
     * Gets all available MIME-types
     *
     * @return array    array[mimetype], array[transformation]
     *
     * @staticvar array $stack
     */
    public function getAvailableMimeTypes(): array
    {
        static $stack = null;

        if ($stack !== null) {
            return $stack;
        }

        $stack = [];
        $sub_dirs = [
            'Input/' => 'input_',
            'Output/' => '',
            '' => '',
        ];

        foreach ($sub_dirs as $sd => $prefix) {
            $handle = opendir(ROOT_PATH . 'libraries/classes/Plugins/Transformations/' . $sd);

            if (! $handle) {
                $stack[$prefix . 'transformation'] = [];
                $stack[$prefix . 'transformation_file'] = [];
                continue;
            }

            $filestack = [];
            while ($file = readdir($handle)) {
                // Ignore hidden files
                if ($file[0] === '.') {
                    continue;
                }

                // Ignore old plugins (.class in filename)
                if (str_contains($file, '.class')) {
                    continue;
                }

                $filestack[] = $file;
            }

            closedir($handle);
            sort($filestack);

            foreach ($filestack as $file) {
                if (preg_match('|^[^.].*_.*_.*\.php$|', $file)) {
                    // File contains transformation functions.
                    $parts = explode('_', str_replace('.php', '', $file));
                    $mimetype = $parts[0] . '/' . $parts[1];
                    $stack['mimetype'][$mimetype] = $mimetype;

                    $stack[$prefix . 'transformation'][] = $mimetype . ': ' . $parts[2];
                    $stack[$prefix . 'transformation_file'][] = $sd . $file;
                    if ($sd === '') {
                        $stack['input_transformation'][] = $mimetype . ': ' . $parts[2];
                        $stack['input_transformation_file'][] = $sd . $file;
                    }
                } elseif (preg_match('|^[^.].*\.php$|', $file)) {
                    // File is a plain mimetype, no functions.
                    $base = str_replace('.php', '', $file);

                    if ($base !== 'global') {
                        $mimetype = str_replace('_', '/', $base);
                        $stack['mimetype'][$mimetype] = $mimetype;
                        $stack['empty_mimetype'][$mimetype] = $mimetype;
                    }
                }
            }
        }

        return $stack;
    }

    /**
     * Returns the class name of the transformation
     *
     * @param string $filename transformation file name
     *
     * @return string the class name of transformation
     */
    public function getClassName($filename)
    {
        // get the transformation class name
        $class_name = explode('.php', $filename);
        $class_name = 'PhpMyAdmin\\' . str_replace('/', '\\', mb_substr($class_name[0], 18));

        return $class_name;
    }

    /**
     * Returns the description of the transformation
     *
     * @param string $file transformation file
     *
     * @return string the description of the transformation
     */
    public function getDescription($file)
    {
        $include_file = 'libraries/classes/Plugins/Transformations/' . $file;
        /** @psalm-var class-string<TransformationsInterface> $class_name */
        $class_name = $this->getClassName($include_file);
        if (class_exists($class_name)) {
            return $class_name::getInfo();
        }

        return '';
    }

    /**
     * Returns the name of the transformation
     *
     * @param string $file transformation file
     *
     * @return string the name of the transformation
     */
    public function getName($file)
    {
        $include_file = 'libraries/classes/Plugins/Transformations/' . $file;
        /** @psalm-var class-string<TransformationsInterface> $class_name */
        $class_name = $this->getClassName($include_file);
        if (class_exists($class_name)) {
            return $class_name::getName();
        }

        return '';
    }

    /**
     * Fixups old MIME or transformation name to new one
     *
     * - applies some hardcoded fixups
     * - adds spaces after _ and numbers
     * - capitalizes words
     * - removes back spaces
     *
     * @param string $value Value to fixup
     *
     * @return string
     */
    public function fixUpMime($value)
    {
        $value = str_replace(
            [
                'jpeg',
                'png',
            ],
            [
                'JPEG',
                'PNG',
            ],
            $value
        );

        return str_replace(
            ' ',
            '',
            ucwords(
                (string) preg_replace('/([0-9_]+)/', '$1 ', $value)
            )
        );
    }

    /**
     * Gets the mimetypes for all columns of a table
     *
     * @param string $db       the name of the db to check for
     * @param string $table    the name of the table to check for
     * @param bool   $strict   whether to include only results having a mimetype set
     * @param bool   $fullName whether to use full column names as the key
     *
     * @return array|null [field_name][field_key] = field_value
     */
    public function getMime($db, $table, $strict = false, $fullName = false)
    {
        global $dbi;

        $relation = new Relation($dbi);
        $browserTransformationFeature = $relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return null;
        }

        $com_qry = '';
        if ($fullName) {
            $com_qry .= 'SELECT CONCAT(`db_name`, \'.\', `table_name`, \'.\', `column_name`) AS column_name, ';
        } else {
            $com_qry = 'SELECT `column_name`, ';
        }

        $com_qry .= '`mimetype`, '
                    . '`transformation`, '
                    . '`transformation_options`, '
                    . '`input_transformation`, '
                    . '`input_transformation_options`'
            . ' FROM ' . Util::backquote($browserTransformationFeature->database) . '.'
            . Util::backquote($browserTransformationFeature->columnInfo)
            . ' WHERE `db_name` = \'' . $dbi->escapeString($db) . '\''
            . ' AND `table_name` = \'' . $dbi->escapeString($table) . '\''
            . ' AND ( `mimetype` != \'\'' . (! $strict ?
                ' OR `transformation` != \'\''
                . ' OR `transformation_options` != \'\''
                . ' OR `input_transformation` != \'\''
                . ' OR `input_transformation_options` != \'\'' : '') . ')';
        $result = $dbi->fetchResult($com_qry, 'column_name', null, DatabaseInterface::CONNECT_CONTROL);

        foreach ($result as $column => $values) {
            // convert mimetype to new format (f.e. Text_Plain, etc)
            $values['mimetype'] = $this->fixUpMime($values['mimetype']);

            // For transformation of form
            // output/image_jpeg__inline.inc.php
            // extract dir part.
            $dir = explode('/', $values['transformation']);
            $subdir = '';
            if (count($dir) === 2) {
                $subdir = ucfirst($dir[0]) . '/';
                $values['transformation'] = $dir[1];
            }

            $values['transformation'] = $this->fixUpMime($values['transformation']);
            $values['transformation'] = $subdir . $values['transformation'];
            $result[$column] = $values;
        }

        return $result;
    }

    /**
     * Set a single mimetype to a certain value.
     *
     * @param string $db                 the name of the db
     * @param string $table              the name of the table
     * @param string $key                the name of the column
     * @param string $mimetype           the mimetype of the column
     * @param string $transformation     the transformation of the column
     * @param string $transformationOpts the transformation options of the column
     * @param string $inputTransform     the input transformation of the column
     * @param string $inputTransformOpts the input transformation options of the column
     * @param bool   $forcedelete        force delete, will erase any existing
     *                                   comments for this column
     */
    public function setMime(
        $db,
        $table,
        $key,
        $mimetype,
        $transformation,
        $transformationOpts,
        $inputTransform,
        $inputTransformOpts,
        $forcedelete = false
    ): bool {
        global $dbi;

        $relation = new Relation($dbi);
        $browserTransformationFeature = $relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return false;
        }

        // lowercase mimetype & transformation
        $mimetype = mb_strtolower($mimetype);
        $transformation = mb_strtolower($transformation);

        // Do we have any parameter to set?
        $has_value = (
            strlen($mimetype) > 0 ||
            strlen($transformation) > 0 ||
            strlen($transformationOpts) > 0 ||
            strlen($inputTransform) > 0 ||
            strlen($inputTransformOpts) > 0
        );

        $test_qry = '
             SELECT `mimetype`,
                    `comment`
               FROM ' . Util::backquote($browserTransformationFeature->database) . '.'
            . Util::backquote($browserTransformationFeature->columnInfo) . '
              WHERE `db_name`     = \'' . $dbi->escapeString($db) . '\'
                AND `table_name`  = \'' . $dbi->escapeString($table) . '\'
                AND `column_name` = \'' . $dbi->escapeString($key) . '\'';

        $test_rs = $dbi->queryAsControlUser($test_qry);

        if ($test_rs->numRows() > 0) {
            $row = $test_rs->fetchAssoc();

            if (! $forcedelete && ($has_value || strlen($row['comment']) > 0)) {
                $upd_query = 'UPDATE '
                    . Util::backquote($browserTransformationFeature->database) . '.'
                    . Util::backquote($browserTransformationFeature->columnInfo)
                    . ' SET '
                    . '`mimetype` = \''
                    . $dbi->escapeString($mimetype) . '\', '
                    . '`transformation` = \''
                    . $dbi->escapeString($transformation) . '\', '
                    . '`transformation_options` = \''
                    . $dbi->escapeString($transformationOpts) . '\', '
                    . '`input_transformation` = \''
                    . $dbi->escapeString($inputTransform) . '\', '
                    . '`input_transformation_options` = \''
                    . $dbi->escapeString($inputTransformOpts) . '\'';
            } else {
                $upd_query = 'DELETE FROM '
                    . Util::backquote($browserTransformationFeature->database)
                    . '.' . Util::backquote($browserTransformationFeature->columnInfo);
            }

            $upd_query .= '
                WHERE `db_name`     = \'' . $dbi->escapeString($db) . '\'
                  AND `table_name`  = \'' . $dbi->escapeString($table)
                    . '\'
                  AND `column_name` = \'' . $dbi->escapeString($key)
                    . '\'';
        } elseif ($has_value) {
            $upd_query = 'INSERT INTO '
                . Util::backquote($browserTransformationFeature->database)
                . '.' . Util::backquote($browserTransformationFeature->columnInfo)
                . ' (db_name, table_name, column_name, mimetype, '
                . 'transformation, transformation_options, '
                . 'input_transformation, input_transformation_options) '
                . ' VALUES('
                . '\'' . $dbi->escapeString($db) . '\','
                . '\'' . $dbi->escapeString($table) . '\','
                . '\'' . $dbi->escapeString($key) . '\','
                . '\'' . $dbi->escapeString($mimetype) . '\','
                . '\'' . $dbi->escapeString($transformation) . '\','
                . '\'' . $dbi->escapeString($transformationOpts) . '\','
                . '\'' . $dbi->escapeString($inputTransform) . '\','
                . '\'' . $dbi->escapeString($inputTransformOpts) . '\')';
        }

        if (isset($upd_query)) {
            return (bool) $dbi->queryAsControlUser($upd_query);
        }

        return false;
    }

    /**
     * GLOBAL Plugin functions
     */

    /**
     * Delete related transformation details
     * after deleting database. table or column
     *
     * @param string $db     Database name
     * @param string $table  Table name
     * @param string $column Column name
     */
    public function clear($db, $table = '', $column = ''): bool
    {
        global $dbi;

        $relation = new Relation($dbi);
        $browserTransformationFeature = $relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return false;
        }

        $delete_sql = 'DELETE FROM '
            . Util::backquote($browserTransformationFeature->database) . '.'
            . Util::backquote($browserTransformationFeature->columnInfo)
            . ' WHERE ';

        if (($column != '') && ($table != '')) {
            $delete_sql .= '`db_name` = \'' . $db . '\' AND '
                . '`table_name` = \'' . $table . '\' AND '
                . '`column_name` = \'' . $column . '\' ';
        } elseif ($table != '') {
            $delete_sql .= '`db_name` = \'' . $db . '\' AND '
                . '`table_name` = \'' . $table . '\' ';
        } else {
            $delete_sql .= '`db_name` = \'' . $db . '\' ';
        }

        return (bool) $dbi->tryQuery($delete_sql);
    }
}
