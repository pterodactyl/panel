<?php
/**
 * Handles visualization of GIS data
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Core;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;
use TCPDF;

use function array_merge;
use function base64_encode;
use function count;
use function htmlspecialchars;
use function intval;
use function is_finite;
use function is_numeric;
use function is_string;
use function mb_strlen;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function ob_get_clean;
use function ob_start;
use function rtrim;

use const PNG_ALL_FILTERS;

/**
 * Handles visualization of GIS data
 */
class GisVisualization
{
    /** @var array   Raw data for the visualization */
    private $data;

    /** @var string */
    private $modifiedSql;

    /** @var array   Set of default settings values are here. */
    private $settings = [
        // Array of colors to be used for GIS visualizations.
        'colors' => [
            '#B02EE0',
            '#E0642E',
            '#E0D62E',
            '#2E97E0',
            '#BCE02E',
            '#E02E75',
            '#5CE02E',
            '#E0B02E',
            '#0022E0',
            '#726CB1',
            '#481A36',
            '#BAC658',
            '#127224',
            '#825119',
            '#238C74',
            '#4C489B',
            '#87C9BF',
        ],


        // Hex values for abovementioned colours
        'colors_hex' => [
            [176, 46, 224],
            [224, 100, 46],
            [224, 214, 46],
            [46, 151, 224],
            [188, 224, 46],
            [224, 46, 117],
            [92, 224, 46],
            [224, 176, 46],
            [0, 34, 224],
            [114, 108, 177],
            [72, 26, 54],
            [186, 198, 88],
            [18, 114, 36],
            [130, 81, 25],
            [35, 140, 116],
            [76, 72, 155],
            [135, 201, 191],
        ],

        // The width of the GIS visualization.
        'width' => 600,
        // The height of the GIS visualization.
        'height' => 450,
    ];

    /** @var array   Options that the user has specified. */
    private $userSpecifiedSettings = null;

    /**
     * Returns the settings array
     *
     * @return array the settings array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Factory
     *
     * @param string $sql_query SQL to fetch raw data for visualization
     * @param array  $options   Users specified options
     * @param int    $row       number of rows
     * @param int    $pos       start position
     *
     * @return GisVisualization
     */
    public static function get($sql_query, array $options, $row, $pos)
    {
        return new GisVisualization($sql_query, $options, $row, $pos);
    }

    /**
     * Get visualization
     *
     * @param array $data    Raw data, if set, parameters other than $options will be
     *                       ignored
     * @param array $options Users specified options
     *
     * @return GisVisualization
     */
    public static function getByData(array $data, array $options)
    {
        return new GisVisualization(null, $options, null, null, $data);
    }

    /**
     * Check if data has SRID
     */
    public function hasSrid(): bool
    {
        foreach ($this->data as $row) {
            if ($row['srid'] != 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stores user specified options.
     *
     * @param string     $sql_query SQL to fetch raw data for visualization
     * @param array      $options   Users specified options
     * @param int        $row       number of rows
     * @param int        $pos       start position
     * @param array|null $data      raw data. If set, parameters other than $options
     *                              will be ignored
     */
    private function __construct($sql_query, array $options, $row, $pos, $data = null)
    {
        $this->userSpecifiedSettings = $options;
        if (isset($data)) {
            $this->data = $data;
        } else {
            $this->modifiedSql = $this->modifySqlQuery($sql_query, $row, $pos);
            $this->data = $this->fetchRawData();
        }
    }

    /**
     * All the variable initialization, options handling has to be done here.
     */
    protected function init(): void
    {
        $this->handleOptions();
    }

    /**
     * Returns sql for fetching raw data
     *
     * @param string $sql_query The SQL to modify.
     * @param int    $rows      Number of rows.
     * @param int    $pos       Start position.
     *
     * @return string the modified sql query.
     */
    private function modifySqlQuery($sql_query, $rows, $pos)
    {
        $isMariaDb = $this->userSpecifiedSettings['isMariaDB'] === true;
        $modified_query = 'SELECT ';
        $spatialAsText = 'ASTEXT';
        $spatialSrid = 'SRID';
        $axisOrder = '';

        if ($this->userSpecifiedSettings['mysqlVersion'] >= 50600) {
            $spatialAsText = 'ST_ASTEXT';
            $spatialSrid = 'ST_SRID';
        }

        // If MYSQL version >= 8.0.1 override default axis order
        if ($this->userSpecifiedSettings['mysqlVersion'] >= 80001 && ! $isMariaDb) {
            $axisOrder = ', \'axis-order=long-lat\'';
        }

        // If label column is chosen add it to the query
        if (! empty($this->userSpecifiedSettings['labelColumn'])) {
            $modified_query .= Util::backquote($this->userSpecifiedSettings['labelColumn'])
            . ', ';
        }

        // Wrap the spatial column with 'ST_ASTEXT()' function and add it
        $modified_query .= $spatialAsText . '('
            . Util::backquote($this->userSpecifiedSettings['spatialColumn'])
            . $axisOrder . ') AS ' . Util::backquote($this->userSpecifiedSettings['spatialColumn'])
            . ', ';

        // Get the SRID
        $modified_query .= $spatialSrid . '('
            . Util::backquote($this->userSpecifiedSettings['spatialColumn'])
            . ') AS ' . Util::backquote('srid') . ' ';

        // Append the original query as the inner query
        $modified_query .= 'FROM (' . rtrim($sql_query, ';') . ') AS '
            . Util::backquote('temp_gis');

        // LIMIT clause
        if (is_numeric($rows) && $rows > 0) {
            $modified_query .= ' LIMIT ';
            if (is_numeric($pos) && $pos >= 0) {
                $modified_query .= $pos . ', ' . $rows;
            } else {
                $modified_query .= $rows;
            }
        }

        return $modified_query;
    }

    /**
     * Returns raw data for GIS visualization.
     *
     * @return array the raw data.
     */
    private function fetchRawData(): array
    {
        global $dbi;

        $modified_result = $dbi->tryQuery($this->modifiedSql);

        if ($modified_result === false) {
            return [];
        }

        return $modified_result->fetchAllAssoc();
    }

    /**
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     */
    private function handleOptions(): void
    {
        if ($this->userSpecifiedSettings === null) {
            return;
        }

        $this->settings = array_merge($this->settings, $this->userSpecifiedSettings);
    }

    /**
     * Sanitizes the file name.
     *
     * @param string $file_name file name
     * @param string $ext       extension of the file
     *
     * @return string the sanitized file name
     */
    private function sanitizeName($file_name, $ext)
    {
        $file_name = Sanitize::sanitizeFilename($file_name);

        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $required_extension = '.' . $ext;
        $extension_length = mb_strlen($required_extension);
        $user_extension = mb_substr($file_name, -$extension_length);
        if (mb_strtolower($user_extension) != $required_extension) {
            $file_name .= $required_extension;
        }

        return $file_name;
    }

    /**
     * Handles common tasks of writing the visualization to file for various formats.
     *
     * @param string $file_name file name
     * @param string $type      mime type
     * @param string $ext       extension of the file
     */
    private function writeToFile($file_name, $type, $ext): void
    {
        $file_name = $this->sanitizeName($file_name, $ext);
        Core::downloadHeader($file_name, $type);
    }

    /**
     * Generate the visualization in SVG format.
     *
     * @return string the generated image resource
     */
    private function svg()
    {
        $this->init();

        $output = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'
            . "\n"
            . '<svg version="1.1" xmlns:svg="http://www.w3.org/2000/svg"'
            . ' xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . intval($this->settings['width']) . '"'
            . ' height="' . intval($this->settings['height']) . '">'
            . '<g id="groupPanel">';

        $scale_data = $this->scaleDataSet($this->data);
        $output .= $this->prepareDataSet($this->data, $scale_data, 'svg', '');

        $output .= '</g></svg>';

        return $output;
    }

    /**
     * Get the visualization as a SVG.
     *
     * @return string the visualization as a SVG
     */
    public function asSVG()
    {
        return $this->svg();
    }

    /**
     * Saves as a SVG image to a file.
     *
     * @param string $file_name File name
     */
    public function toFileAsSvg($file_name): void
    {
        $img = $this->svg();
        $this->writeToFile($file_name, 'image/svg+xml', 'svg');
        echo $img;
    }

    /**
     * Generate the visualization in PNG format.
     *
     * @return ImageWrapper|null the generated image resource
     */
    private function png(): ?ImageWrapper
    {
        $this->init();

        $image = ImageWrapper::create(
            $this->settings['width'],
            $this->settings['height'],
            ['red' => 229, 'green' => 229, 'blue' => 229]
        );
        if ($image === null) {
            return null;
        }

        $scale_data = $this->scaleDataSet($this->data);
        /** @var ImageWrapper $image */
        $image = $this->prepareDataSet($this->data, $scale_data, 'png', $image);

        return $image;
    }

    /**
     * Get the visualization as a PNG.
     *
     * @return string the visualization as a PNG
     */
    public function asPng()
    {
        $image = $this->png();
        if ($image === null) {
            return '';
        }

        // render and save it to variable
        ob_start();
        $image->png(null, 9, PNG_ALL_FILTERS);
        $image->destroy();
        $output = ob_get_clean();

        // base64 encode
        $encoded = base64_encode((string) $output);

        return '<img src="data:image/png;base64,' . $encoded . '">';
    }

    /**
     * Saves as a PNG image to a file.
     *
     * @param string $file_name File name
     */
    public function toFileAsPng($file_name): void
    {
        $image = $this->png();
        if ($image === null) {
            return;
        }

        $this->writeToFile($file_name, 'image/png', 'png');
        $image->png(null, 9, PNG_ALL_FILTERS);
        $image->destroy();
    }

    /**
     * Get the code for visualization with OpenLayers.
     *
     * @return string the code for visualization with OpenLayers
     *
     * @todo Should return JSON to avoid eval() in gis_data_editor.js
     */
    public function asOl()
    {
        $this->init();
        $scale_data = $this->scaleDataSet($this->data);
        $output = 'function drawOpenLayers() {'
            . 'if (typeof ol !== "undefined") {'
            . 'var olCss = "js/vendor/openlayers/theme/ol.css";'
            . 'if (!document.querySelector(\'link[rel="stylesheet"][href="\' + olCss + \'"]\')) {'
            . 'var link = document.createElement(\'link\');'
            . 'link.rel = \'stylesheet\';'
            . 'link.type = \'text/css\';'
            . 'link.href = olCss;'
            . 'document.head.appendChild(link);'
            . '}'
            . 'var vectorLayer = new ol.source.Vector({});'
            . 'var map = new ol.Map({'
            . 'target: \'openlayersmap\','
            . 'layers: ['
            . 'new ol.layer.Tile({'
            . 'source: new ol.source.OSM()'
            . '}),'
            . 'new ol.layer.Vector({'
            . 'source: vectorLayer'
            . '})'
            . '],'
            . 'view: new ol.View({'
            . 'center: ol.proj.fromLonLat([37.41, 8.82]),'
            . 'zoom: 4'
            . '}),'
            . 'controls: [new ol.control.MousePosition({'
            . 'coordinateFormat: ol.coordinate.createStringXY(4),'
            . 'projection: \'EPSG:4326\'}),'
            . 'new ol.control.Zoom,'
            . 'new ol.control.Attribution]'
            . '});';
        $output .= $this->prepareDataSet($this->data, $scale_data, 'ol', '')
            . 'return map;'
            . '}'
            . 'return undefined;'
            . '}';

        return $output;
    }

    /**
     * Saves as a PDF to a file.
     *
     * @param string $file_name File name
     */
    public function toFileAsPdf($file_name): void
    {
        $this->init();

        // create pdf
        $pdf = new TCPDF('', 'pt', $GLOBALS['cfg']['PDFDefaultPageSize'], true, 'UTF-8', false);

        // disable header and footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        //set auto page breaks
        $pdf->setAutoPageBreak(false);

        // add a page
        $pdf->AddPage();

        $scale_data = $this->scaleDataSet($this->data);
        $pdf = $this->prepareDataSet($this->data, $scale_data, 'pdf', $pdf);

        // sanitize file name
        $file_name = $this->sanitizeName($file_name, 'pdf');
        $pdf->Output($file_name, 'D');
    }

    /**
     * Convert file to image
     *
     * @param string $format Output format
     *
     * @return string File
     */
    public function toImage($format)
    {
        if ($format === 'svg') {
            return $this->asSVG();
        }

        if ($format === 'png') {
            return $this->asPng();
        }

        if ($format === 'ol') {
            return $this->asOl();
        }

        return '';
    }

    /**
     * Convert file to given format
     *
     * @param string $filename Filename
     * @param string $format   Output format
     */
    public function toFile($filename, $format): void
    {
        if ($format === 'svg') {
            $this->toFileAsSvg($filename);
        } elseif ($format === 'png') {
            $this->toFileAsPng($filename);
        } elseif ($format === 'pdf') {
            $this->toFileAsPdf($filename);
        }
    }

    /**
     * Calculates the scale, horizontal and vertical offset that should be used.
     *
     * @param array $data Row data
     *
     * @return array an array containing the scale, x and y offsets
     */
    private function scaleDataSet(array $data)
    {
        $min_max = GisGeometry::EMPTY_EXTENT;
        $border = 15;
        // effective width and height of the plot
        $plot_width = $this->settings['width'] - 2 * $border;
        $plot_height = $this->settings['height'] - 2 * $border;

        foreach ($data as $row) {
            // Figure out the data type
            $ref_data = $row[$this->settings['spatialColumn']];
            if (! is_string($ref_data)) {
                continue;
            }

            $type_pos = mb_strpos($ref_data, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($ref_data, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $scale_data = $gis_obj->scaleRow($row[$this->settings['spatialColumn']]);

            // Update minimum/maximum values for x and y coordinates.
            $c_maxX = (float) $scale_data['maxX'];
            if ($c_maxX > $min_max['maxX']) {
                $min_max['maxX'] = $c_maxX;
            }

            $c_minX = (float) $scale_data['minX'];
            if ($c_minX < $min_max['minX']) {
                $min_max['minX'] = $c_minX;
            }

            $c_maxY = (float) $scale_data['maxY'];
            if ($c_maxY > $min_max['maxY']) {
                $min_max['maxY'] = $c_maxY;
            }

            $c_minY = (float) $scale_data['minY'];
            if ($c_minY >= $min_max['minY']) {
                continue;
            }

            $min_max['minY'] = $c_minY;
        }

        if (! is_finite($min_max['minX']) || ! is_finite($min_max['minY'])) {
            $min_max['maxX'] = 0.0;
            $min_max['maxY'] = 0.0;
            $min_max['minX'] = 0.0;
            $min_max['minY'] = 0.0;
        }

        // scale the visualization
        $x_ratio = ($min_max['maxX'] - $min_max['minX']) / $plot_width;
        $y_ratio = ($min_max['maxY'] - $min_max['minY']) / $plot_height;
        $ratio = $x_ratio > $y_ratio ? $x_ratio : $y_ratio;

        $scale = $ratio != 0 ? 1 / $ratio : 1;

        // Center plot
        $x = $ratio == 0 || $x_ratio < $y_ratio
            ? ($min_max['maxX'] + $min_max['minX'] - $this->settings['width'] / $scale) / 2
            : $min_max['minX'] - ($border / $scale);
        $y = $ratio == 0 || $x_ratio >= $y_ratio
            ? ($min_max['maxY'] + $min_max['minY'] - $this->settings['height'] / $scale) / 2
            : $min_max['minY'] - ($border / $scale);

        return [
            'scale' => $scale,
            'x' => $x,
            'y' => $y,
            'minX' => $min_max['minX'],
            'maxX' => $min_max['maxX'],
            'minY' => $min_max['minY'],
            'maxY' => $min_max['maxY'],
            'height' => $this->settings['height'],
        ];
    }

    /**
     * Prepares and return the dataset as needed by the visualization.
     *
     * @param array                           $data       Raw data
     * @param array                           $scale_data Data related to scaling
     * @param string                          $format     Format of the visualization
     * @param ImageWrapper|TCPDF|string|false $results    Image object in the case of png
     *                                                    TCPDF object in the case of pdf
     *
     * @return mixed the formatted array of data
     */
    private function prepareDataSet(array $data, array $scale_data, $format, $results)
    {
        $color_number = 0;

        // loop through the rows
        foreach ($data as $row) {
            $index = $color_number % count($this->settings['colors']);

            // Figure out the data type
            $ref_data = $row[$this->settings['spatialColumn']];
            if (! is_string($ref_data)) {
                continue;
            }

            $type_pos = mb_strpos($ref_data, '(');
            if ($type_pos === false) {
                continue;
            }

            $type = mb_substr($ref_data, 0, $type_pos);

            $gis_obj = GisFactory::factory($type);
            if (! $gis_obj) {
                continue;
            }

            $label = '';
            if (isset($this->settings['labelColumn'], $row[$this->settings['labelColumn']])) {
                $label = $row[$this->settings['labelColumn']];
            }

            if ($format === 'svg') {
                $results .= $gis_obj->prepareRowAsSvg(
                    $row[$this->settings['spatialColumn']],
                    htmlspecialchars($label),
                    $this->settings['colors'][$index],
                    $scale_data
                );
            } elseif ($format === 'png') {
                $results = $gis_obj->prepareRowAsPng(
                    $row[$this->settings['spatialColumn']],
                    $label,
                    $this->settings['colors'][$index],
                    $scale_data,
                    $results
                );
            } elseif ($format === 'pdf' && $results instanceof TCPDF) {
                $results = $gis_obj->prepareRowAsPdf(
                    $row[$this->settings['spatialColumn']],
                    $label,
                    $this->settings['colors'][$index],
                    $scale_data,
                    $results
                );
            } elseif ($format === 'ol') {
                $results .= $gis_obj->prepareRowAsOl(
                    $row[$this->settings['spatialColumn']],
                    (int) $row['srid'],
                    $label,
                    $this->settings['colors_hex'][$index],
                    $scale_data
                );
            }

            $color_number++;
        }

        return $results;
    }

    /**
     * Set user specified settings
     *
     * @param array $userSpecifiedSettings User specified settings
     */
    public function setUserSpecifiedSettings(array $userSpecifiedSettings): void
    {
        $this->userSpecifiedSettings = $userSpecifiedSettings;
    }
}
