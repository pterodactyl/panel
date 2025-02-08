<?php
/**
 * Handles actions related to GIS POINT objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function hexdec;
use function json_encode;
use function mb_substr;
use function round;
use function trim;

/**
 * Handles actions related to GIS POINT objects
 */
class GisPoint extends GisGeometry
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
     * @return GisPoint the singleton
     */
    public static function singleton()
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisPoint();
        }

        return self::$instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array an array containing the min, max values for x and y coordinates
     * @psalm-return array{minX:float,minY:float,maxX:float,maxY:float}
     */
    public function scaleRow($spatial)
    {
        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);

        return $this->setMinMax($point, GisGeometry::EMPTY_EXTENT);
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial     GIS POLYGON object
     * @param string|null $label       Label for the GIS POLYGON object
     * @param string      $point_color Color for the GIS POLYGON object
     * @param array       $scale_data  Array containing data related to scaling
     */
    public function prepareRowAsPng(
        $spatial,
        ?string $label,
        $point_color,
        array $scale_data,
        ImageWrapper $image
    ): ImageWrapper {
        // allocate colors
        $black = $image->colorAllocate(0, 0, 0);
        $red = (int) hexdec(mb_substr($point_color, 1, 2));
        $green = (int) hexdec(mb_substr($point_color, 3, 2));
        $blue = (int) hexdec(mb_substr($point_color, 4, 2));
        $color = $image->colorAllocate($red, $green, $blue);

        $label = trim($label ?? '');

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints($point, $scale_data);

        // draw a small circle to mark the point
        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            $image->arc(
                (int) round($points_arr[0][0]),
                (int) round($points_arr[0][1]),
                7,
                7,
                0,
                360,
                $color
            );
            // print label if applicable
            if ($label !== '') {
                $image->string(
                    1,
                    (int) round($points_arr[0][0]),
                    (int) round($points_arr[0][1]),
                    $label,
                    $black
                );
            }
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial     GIS POINT object
     * @param string|null $label       Label for the GIS POINT object
     * @param string      $point_color Color for the GIS POINT object
     * @param array       $scale_data  Array containing data related to scaling
     * @param TCPDF       $pdf         TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf(
        $spatial,
        ?string $label,
        $point_color,
        array $scale_data,
        $pdf
    ) {
        // allocate colors
        $red = hexdec(mb_substr($point_color, 1, 2));
        $green = hexdec(mb_substr($point_color, 3, 2));
        $blue = hexdec(mb_substr($point_color, 4, 2));
        $line = [
            'width' => 1.25,
            'color' => [
                $red,
                $green,
                $blue,
            ],
        ];

        $label = trim($label ?? '');

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints($point, $scale_data);

        // draw a small circle to mark the point
        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            $pdf->Circle($points_arr[0][0], $points_arr[0][1], 2, 0, 360, 'D', $line);
            // print label if applicable
            if ($label !== '') {
                $pdf->setXY($points_arr[0][0], $points_arr[0][1]);
                $pdf->setFontSize(5);
                $pdf->Cell(0, 0, $label);
            }
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $point_color, array $scale_data)
    {
        $point_options = [
            'data-label' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'point vector',
            'fill' => 'white',
            'stroke' => $point_color,
            'stroke-width' => 2,
        ];

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints($point, $scale_data);

        $row = '';
        if (((float) $points_arr[0][0]) !== 0.0 && ((float) $points_arr[0][1]) !== 0.0) {
            $row .= '<circle cx="' . $points_arr[0][0]
                . '" cy="' . $points_arr[0][1] . '" r="3"';
            foreach ($point_options as $option => $val) {
                $row .= ' ' . $option . '="' . trim((string) $val) . '"';
            }

            $row .= '/>';
        }

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial     GIS POINT object
     * @param int    $srid        Spatial reference ID
     * @param string $label       Label for the GIS POINT object
     * @param array  $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl(
        $spatial,
        int $srid,
        $label,
        $point_color,
        array $scale_data
    ) {
        $fill_style = ['color' => 'white'];
        $stroke_style = [
            'color' => $point_color,
            'width' => 2,
        ];
        $result = 'var fill = new ol.style.Fill(' . json_encode($fill_style) . ');'
            . 'var stroke = new ol.style.Stroke(' . json_encode($stroke_style) . ');'
            . 'var style = new ol.style.Style({'
            . 'image: new ol.style.Circle({'
            . 'fill: fill,'
            . 'stroke: stroke,'
            . 'radius: 3'
            . '}),'
            . 'fill: fill,'
            . 'stroke: stroke';

        if (trim($label) !== '') {
            $text_style = [
                'text' => trim($label),
                'offsetY' => -9,
            ];
            $result .= ',text: new ol.style.Text(' . json_encode($text_style) . ')';
        }

        $result .= '});';

        if ($srid === 0) {
            $srid = 4326;
        }

        $result .= $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($spatial, 6, -1);
        $points_arr = $this->extractPoints($point, null);

        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            $result .= 'var point = new ol.Feature({geometry: '
                . $this->getPointForOpenLayers($points_arr[0], $srid) . '});'
                . 'point.setStyle(style);'
                . 'vectorLayer.addFeature(point);';
        }

        return $result;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array       $gis_data GIS data
     * @param int         $index    Index into the parameter object
     * @param string|null $empty    Point does not adhere to this parameter
     *
     * @return string WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt(array $gis_data, $index, $empty = '')
    {
        return 'POINT('
        . (isset($gis_data[$index]['POINT']['x'])
            && trim((string) $gis_data[$index]['POINT']['x']) != ''
            ? $gis_data[$index]['POINT']['x'] : '')
        . ' '
        . (isset($gis_data[$index]['POINT']['y'])
            && trim((string) $gis_data[$index]['POINT']['y']) != ''
            ? $gis_data[$index]['POINT']['y'] : '') . ')';
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param array $row_data GIS data
     *
     * @return string the WKT for the data from ESRI shape files
     */
    public function getShape(array $row_data)
    {
        return 'POINT(' . ($row_data['x'] ?? '')
        . ' ' . ($row_data['y'] ?? '') . ')';
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
            $params[$index]['gis_type'] = 'POINT';
            $wkt = $value;
        }

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = mb_substr($wkt, 6, -1);
        $points_arr = $this->extractPoints($point, null);

        $params[$index]['POINT']['x'] = $points_arr[0][0];
        $params[$index]['POINT']['y'] = $points_arr[0][1];

        return $params;
    }
}
