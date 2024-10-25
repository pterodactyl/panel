<?php
/**
 * Handles actions related to GIS LINESTRING objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function count;
use function hexdec;
use function json_encode;
use function mb_substr;
use function round;
use function trim;

/**
 * Handles actions related to GIS LINESTRING objects
 */
class GisLineString extends GisGeometry
{
    /** @var self */
    private static $instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return GisLineString the singleton
     */
    public static function singleton()
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisLineString();
        }

        return self::$instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array an array containing the min, max values for x and y coordinates
     */
    public function scaleRow($spatial)
    {
        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = mb_substr($spatial, 11, -1);

        return $this->setMinMax($linestring, []);
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS POLYGON object
     * @param string|null $label      Label for the GIS POLYGON object
     * @param string      $line_color Color for the GIS POLYGON object
     * @param array       $scale_data Array containing data related to scaling
     */
    public function prepareRowAsPng(
        $spatial,
        ?string $label,
        $line_color,
        array $scale_data,
        ImageWrapper $image
    ): ImageWrapper {
        // allocate colors
        $black = $image->colorAllocate(0, 0, 0);
        $red = (int) hexdec(mb_substr($line_color, 1, 2));
        $green = (int) hexdec(mb_substr($line_color, 3, 2));
        $blue = (int) hexdec(mb_substr($line_color, 4, 2));
        $color = $image->colorAllocate($red, $green, $blue);

        $label = trim($label ?? '');

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $lineString = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($lineString, $scale_data);

        foreach ($points_arr as $point) {
            if (isset($temp_point)) {
                // draw line section
                $image->line(
                    (int) round($temp_point[0]),
                    (int) round($temp_point[1]),
                    (int) round($point[0]),
                    (int) round($point[1]),
                    $color
                );
            }

            $temp_point = $point;
        }

        // print label if applicable
        if ($label !== '') {
            $image->string(
                1,
                (int) round($points_arr[1][0]),
                (int) round($points_arr[1][1]),
                $label,
                $black
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS LINESTRING object
     * @param string|null $label      Label for the GIS LINESTRING object
     * @param string      $line_color Color for the GIS LINESTRING object
     * @param array       $scale_data Array containing data related to scaling
     * @param TCPDF       $pdf        TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, ?string $label, $line_color, array $scale_data, $pdf)
    {
        // allocate colors
        $red = hexdec(mb_substr($line_color, 1, 2));
        $green = hexdec(mb_substr($line_color, 3, 2));
        $blue = hexdec(mb_substr($line_color, 4, 2));
        $line = [
            'width' => 1.5,
            'color' => [
                $red,
                $green,
                $blue,
            ],
        ];

        $label = trim($label ?? '');

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($linesrting, $scale_data);

        foreach ($points_arr as $point) {
            if (isset($temp_point)) {
                // draw line section
                $pdf->Line($temp_point[0], $temp_point[1], $point[0], $point[1], $line);
            }

            $temp_point = $point;
        }

        // print label
        if ($label !== '') {
            $pdf->setXY($points_arr[1][0], $points_arr[1][1]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      Label for the GIS LINESTRING object
     * @param string $line_color Color for the GIS LINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $line_color, array $scale_data)
    {
        $line_options = [
            'name' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'linestring vector',
            'fill' => 'none',
            'stroke' => $line_color,
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($linesrting, $scale_data);

        $row = '<polyline points="';
        foreach ($points_arr as $point) {
            $row .= $point[0] . ',' . $point[1] . ' ';
        }

        $row .= '"';
        foreach ($line_options as $option => $val) {
            $row .= ' ' . $option . '="' . trim((string) $val) . '"';
        }

        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS LINESTRING object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS LINESTRING object
     * @param array  $line_color Color for the GIS LINESTRING object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, int $srid, $label, $line_color, array $scale_data)
    {
        $stroke_style = [
            'color' => $line_color,
            'width' => 2,
        ];

        $result = 'var style = new ol.style.Style({'
            . 'stroke: new ol.style.Stroke(' . json_encode($stroke_style) . ')';
        if (trim($label) !== '') {
            $text_style = ['text' => trim($label)];
            $result .= ', text: new ol.style.Text(' . json_encode($text_style) . ')';
        }

        $result .= '});';

        if ($srid === 0) {
            $srid = 4326;
        }

        $result .= $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linesrting = mb_substr($spatial, 11, -1);
        $points_arr = $this->extractPoints($linesrting, null);

        return $result . 'var line = new ol.Feature({geometry: '
            . $this->getLineForOpenLayers($points_arr, $srid) . '});'
            . 'line.setStyle(style);'
            . 'vectorLayer.addFeature(line);';
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array       $gis_data GIS data
     * @param int         $index    Index into the parameter object
     * @param string|null $empty    Value for empty points
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gis_data, $index, $empty = '')
    {
        $no_of_points = $gis_data[$index]['LINESTRING']['no_of_points'] ?? 2;
        if ($no_of_points < 2) {
            $no_of_points = 2;
        }

        $wkt = 'LINESTRING(';
        for ($i = 0; $i < $no_of_points; $i++) {
            $wkt .= (isset($gis_data[$index]['LINESTRING'][$i]['x'])
                    && trim((string) $gis_data[$index]['LINESTRING'][$i]['x']) != ''
                    ? $gis_data[$index]['LINESTRING'][$i]['x'] : $empty)
                . ' ' . (isset($gis_data[$index]['LINESTRING'][$i]['y'])
                    && trim((string) $gis_data[$index]['LINESTRING'][$i]['y']) != ''
                    ? $gis_data[$index]['LINESTRING'][$i]['y'] : $empty) . ',';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     * @param int    $index of the geometry
     *
     * @return array params for the GIS data editor from the value of the GIS column
     */
    public function generateParams($value, $index = -1)
    {
        $params = [];
        if ($index == -1) {
            $index = 0;
            $data = GisGeometry::generateParams($value);
            $params['srid'] = $data['srid'];
            $wkt = $data['wkt'];
        } else {
            $params[$index]['gis_type'] = 'LINESTRING';
            $wkt = $value;
        }

        // Trim to remove leading 'LINESTRING(' and trailing ')'
        $linestring = mb_substr($wkt, 11, -1);
        $points_arr = $this->extractPoints($linestring, null);

        $no_of_points = count($points_arr);
        $params[$index]['LINESTRING']['no_of_points'] = $no_of_points;
        for ($i = 0; $i < $no_of_points; $i++) {
            $params[$index]['LINESTRING'][$i]['x'] = $points_arr[$i][0];
            $params[$index]['LINESTRING'][$i]['y'] = $points_arr[$i][1];
        }

        return $params;
    }
}
