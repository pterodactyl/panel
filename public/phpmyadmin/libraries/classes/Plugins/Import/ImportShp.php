<?php
/**
 * ESRI Shape file import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Gis\GisPoint;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\ZipExtension;
use ZipArchive;

use function __;
use function count;
use function extension_loaded;
use function file_exists;
use function file_put_contents;
use function mb_substr;
use function method_exists;
use function pathinfo;
use function strcmp;
use function strlen;
use function substr;
use function trim;
use function unlink;

use const LOCK_EX;

/**
 * Handles the import for ESRI Shape files
 */
class ImportShp extends ImportPlugin
{
    /** @var ZipExtension|null */
    private $zipExtension = null;

    protected function init(): void
    {
        if (! extension_loaded('zip')) {
            return;
        }

        $this->zipExtension = new ZipExtension(new ZipArchive());
    }

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'shp';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('ESRI Shape File'));
        $importPluginProperties->setExtension('shp');
        $importPluginProperties->setOptionsText(__('Options'));

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array $sql_data 2-element array with sql data
     */
    public function doImport(?File $importHandle = null, array &$sql_data = []): void
    {
        global $db, $error, $finished, $import_file, $local_import_file, $message, $dbi;

        $GLOBALS['finished'] = false;

        if ($importHandle === null || $this->zipExtension === null) {
            return;
        }

        /** @see ImportShp::readFromBuffer() */
        $GLOBALS['importHandle'] = $importHandle;

        $compression = $importHandle->getCompression();

        $shp = new ShapeFileImport(1);
        // If the zip archive has more than one file,
        // get the correct content to the buffer from .shp file.
        if ($compression === 'application/zip' && $this->zipExtension->getNumberOfFiles($import_file) > 1) {
            if ($importHandle->openZip('/^.*\.shp$/i') === false) {
                $message = Message::error(
                    __('There was an error importing the ESRI shape file: "%s".')
                );
                $message->addParam($importHandle->getError());

                return;
            }
        }

        $temp_dbf_file = false;
        // We need dbase extension to handle .dbf file
        if (extension_loaded('dbase')) {
            $temp = $GLOBALS['config']->getTempDir('shp');
            // If we can extract the zip archive to 'TempDir'
            // and use the files in it for import
            if ($compression === 'application/zip' && $temp !== null) {
                $dbf_file_name = $this->zipExtension->findFile($import_file, '/^.*\.dbf$/i');
                // If the corresponding .dbf file is in the zip archive
                if ($dbf_file_name) {
                    // Extract the .dbf file and point to it.
                    $extracted = $this->zipExtension->extract($import_file, $dbf_file_name);
                    if ($extracted !== false) {
                        // remove filename extension, e.g.
                        // dresden_osm.shp/gis.osm_transport_a_v06.dbf
                        // to
                        // dresden_osm.shp/gis.osm_transport_a_v06
                        $path_parts = pathinfo($dbf_file_name);
                        $dbf_file_name = $path_parts['dirname'] . '/' . $path_parts['filename'];

                        // sanitize filename
                        $dbf_file_name = Sanitize::sanitizeFilename($dbf_file_name, true);

                        // concat correct filename and extension
                        $dbf_file_path = $temp . '/' . $dbf_file_name . '.dbf';

                        if (file_put_contents($dbf_file_path, $extracted, LOCK_EX) !== false) {
                            $temp_dbf_file = true;

                            // Replace the .dbf with .*, as required by the bsShapeFiles library.
                            $shp->fileName = substr($dbf_file_path, 0, -4) . '.*';
                        }
                    }
                }
            } elseif (! empty($local_import_file) && ! empty($GLOBALS['cfg']['UploadDir']) && $compression === 'none') {
                // If file is in UploadDir, use .dbf file in the same UploadDir
                // to load extra data.
                // Replace the .shp with .*,
                // so the bsShapeFiles library correctly locates .dbf file.
                $shp->fileName = mb_substr($import_file, 0, -4) . '.*';
            }
        }

        // It should load data before file being deleted
        $shp->loadFromFile('');

        // Delete the .dbf file extracted to 'TempDir'
        if ($temp_dbf_file && isset($dbf_file_path) && @file_exists($dbf_file_path)) {
            unlink($dbf_file_path);
        }

        if ($shp->lastError != '') {
            $error = true;
            $message = Message::error(
                __('There was an error importing the ESRI shape file: "%s".')
            );
            $message->addParam($shp->lastError);

            return;
        }

        switch ($shp->shapeType) {
            // ESRI Null Shape
            case 0:
                break;
            // ESRI Point
            case 1:
                $gis_type = 'point';
                break;
            // ESRI PolyLine
            case 3:
                $gis_type = 'multilinestring';
                break;
            // ESRI Polygon
            case 5:
                $gis_type = 'multipolygon';
                break;
            // ESRI MultiPoint
            case 8:
                $gis_type = 'multipoint';
                break;
            default:
                $error = true;
                $message = Message::error(
                    __('MySQL Spatial Extension does not support ESRI type "%s".')
                );
                $message->addParam($shp->getShapeName());

                return;
        }

        if (isset($gis_type)) {
            /** @var GisMultiLineString|GisMultiPoint|GisPoint|GisPolygon $gis_obj */
            $gis_obj = GisFactory::factory($gis_type);
        } else {
            $gis_obj = null;
        }

        $num_rows = count($shp->records);
        // If .dbf file is loaded, the number of extra data columns
        $num_data_cols = $shp->getDBFHeader() !== null ? count($shp->getDBFHeader()) : 0;

        $rows = [];
        $col_names = [];
        if ($num_rows != 0) {
            foreach ($shp->records as $record) {
                $tempRow = [];
                if ($gis_obj == null || ! method_exists($gis_obj, 'getShape')) {
                    $tempRow[] = null;
                } else {
                    $tempRow[] = "GeomFromText('"
                        . $gis_obj->getShape($record->shpData) . "')";
                }

                if ($shp->getDBFHeader() !== null) {
                    foreach ($shp->getDBFHeader() as $c) {
                        $cell = trim((string) $record->dbfData[$c[0]]);

                        if (! strcmp($cell, '')) {
                            $cell = 'NULL';
                        }

                        $tempRow[] = $cell;
                    }
                }

                $rows[] = $tempRow;
            }
        }

        if (count($rows) === 0) {
            $error = true;
            $message = Message::error(
                __('The imported file does not contain any data!')
            );

            return;
        }

        // Column names for spatial column and the rest of the columns,
        // if they are available
        $col_names[] = 'SPATIAL';
        $dbfHeader = $shp->getDBFHeader();
        for ($n = 0; $n < $num_data_cols; $n++) {
            if ($dbfHeader === null) {
                continue;
            }

            $col_names[] = $dbfHeader[$n][0];
        }

        // Set table name based on the number of tables
        if (strlen((string) $db) > 0) {
            $result = $dbi->fetchResult('SHOW TABLES');
            $table_name = 'TABLE ' . (count($result) + 1);
        } else {
            $table_name = 'TBL_NAME';
        }

        $tables = [
            [
                $table_name,
                $col_names,
                $rows,
            ],
        ];

        // Use data from shape file to chose best-fit MySQL types for each column
        $analyses = [];
        $analyses[] = $this->import->analyzeTable($tables[0]);

        $table_no = 0;
        $spatial_col = 0;
        $analyses[$table_no][Import::TYPES][$spatial_col] = Import::GEOMETRY;
        $analyses[$table_no][Import::FORMATTEDSQL][$spatial_col] = true;

        // Set database name to the currently selected one, if applicable
        if (strlen((string) $db) > 0) {
            $db_name = $db;
            $options = ['create_db' => false];
        } else {
            $db_name = 'SHP_DB';
            $options = null;
        }

        // Created and execute necessary SQL statements from data
        $null_param = null;
        $this->import->buildSql($db_name, $tables, $analyses, $null_param, $options, $sql_data);

        unset($tables, $analyses);

        $finished = true;
        $error = false;

        // Commit any possible data in buffers
        $this->import->runQuery('', '', $sql_data);
    }

    /**
     * Returns specified number of bytes from the buffer.
     * Buffer automatically fetches next chunk of data when the buffer
     * falls short.
     * Sets $eof when $GLOBALS['finished'] is set and the buffer falls short.
     *
     * @param int $length number of bytes
     *
     * @return string
     */
    public static function readFromBuffer($length)
    {
        global $buffer, $eof, $importHandle;

        $import = new Import();

        if (strlen((string) $buffer) < $length) {
            if ($GLOBALS['finished']) {
                $eof = true;
            } else {
                $buffer .= $import->getNextChunk($importHandle);
            }
        }

        $result = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length);

        return $result;
    }
}
