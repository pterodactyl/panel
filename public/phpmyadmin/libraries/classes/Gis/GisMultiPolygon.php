<?php
/**
 * Handles actions related to GIS MULTIPOLYGON objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

use function array_merge;
use function array_slice;
use function count;
use function explode;
use function hexdec;
use function json_encode;
use function mb_substr;
use function round;
use function str_contains;
use function trim;

/**
 * Handles actions related to GIS MULTIPOLYGON objects
 */
class GisMultiPolygon extends GisGeometry
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
     * @return GisMultiPolygon the singleton
     */
    public static function singleton()
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisMultiPolygon();
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
        $min_max = GisGeometry::EMPTY_EXTENT;

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner ring, use polygon itself
            if (! str_contains($polygon, '),(')) {
                $ring = $polygon;
            } else {
                // Separate outer ring and use it to determine min-max
                $parts = explode('),(', $polygon);
                $ring = $parts[0];
            }

            $min_max = $this->setMinMax($ring, $min_max);
        }

        return $min_max;
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS POLYGON object
     * @param string|null $label      Label for the GIS POLYGON object
     * @param string      $fill_color Color for the GIS POLYGON object
     * @param array       $scale_data Array containing data related to scaling
     */
    public function prepareRowAsPng(
        $spatial,
        ?string $label,
        $fill_color,
        array $scale_data,
        ImageWrapper $image
    ): ImageWrapper {
        // allocate colors
        $black = $image->colorAllocate(0, 0, 0);
        $red = (int) hexdec(mb_substr($fill_color, 1, 2));
        $green = (int) hexdec(mb_substr($fill_color, 3, 2));
        $blue = (int) hexdec(mb_substr($fill_color, 4, 2));
        $color = $image->colorAllocate($red, $green, $blue);

        $label = trim($label ?? '');

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        $first_poly = true;
        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner polygon
            if (! str_contains($polygon, '),(')) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Separate outer and inner polygons
                $parts = explode('),(', $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $points_arr = $this->extractPoints($outer, $scale_data, true);

                foreach ($inner as $inner_poly) {
                    $points_arr = array_merge(
                        $points_arr,
                        $this->extractPoints($inner_poly, $scale_data, true)
                    );
                }
            }

            // draw polygon
            $image->filledPolygon($points_arr, $color);
            // mark label point if applicable
            if ($label !== '' && $first_poly) {
                $label_point = [
                    $points_arr[2],
                    $points_arr[3],
                ];
            }

            $first_poly = false;
        }

        // print label if applicable
        if (isset($label_point)) {
            $image->string(
                1,
                (int) round($label_point[0]),
                (int) round($label_point[1]),
                $label,
                $black
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS MULTIPOLYGON object
     * @param string|null $label      Label for the GIS MULTIPOLYGON object
     * @param string      $fill_color Color for the GIS MULTIPOLYGON object
     * @param array       $scale_data Array containing data related to scaling
     * @param TCPDF       $pdf        TCPDF instance
     *
     * @return TCPDF the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, ?string $label, $fill_color, array $scale_data, $pdf)
    {
        // allocate colors
        $red = hexdec(mb_substr($fill_color, 1, 2));
        $green = hexdec(mb_substr($fill_color, 3, 2));
        $blue = hexdec(mb_substr($fill_color, 4, 2));
        $color = [
            $red,
            $green,
            $blue,
        ];

        $label = trim($label ?? '');

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        $first_poly = true;
        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner polygon
            if (! str_contains($polygon, '),(')) {
                $points_arr = $this->extractPoints($polygon, $scale_data, true);
            } else {
                // Separate outer and inner polygons
                $parts = explode('),(', $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $points_arr = $this->extractPoints($outer, $scale_data, true);

                foreach ($inner as $inner_poly) {
                    $points_arr = array_merge(
                        $points_arr,
                        $this->extractPoints($inner_poly, $scale_data, true)
                    );
                }
            }

            // draw polygon
            $pdf->Polygon($points_arr, 'F*', [], $color, true);
            // mark label point if applicable
            if ($label !== '' && $first_poly) {
                $label_point = [
                    $points_arr[2],
                    $points_arr[3],
                ];
            }

            $first_poly = false;
        }

        // print label if applicable
        if (isset($label_point)) {
            $pdf->setXY($label_point[0], $label_point[1]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param string $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $fill_color, array $scale_data)
    {
        $polygon_options = [
            'data-label' => $label,
            'class' => 'multipolygon vector',
            'stroke' => 'black',
            'stroke-width' => 0.5,
            'fill' => $fill_color,
            'fill-rule' => 'evenodd',
            'fill-opacity' => 0.8,
        ];

        $row = '';

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        foreach ($polygons as $polygon) {
            $row .= '<path d="';

            // If the polygon doesn't have an inner polygon
            if (! str_contains($polygon, '),(')) {
                $row .= $this->drawPath($polygon, $scale_data);
            } else {
                // Separate outer and inner polygons
                $parts = explode('),(', $polygon);
                $outer = $parts[0];
                $inner = array_slice($parts, 1);

                $row .= $this->drawPath($outer, $scale_data);

                foreach ($inner as $inner_poly) {
                    $row .= $this->drawPath($inner_poly, $scale_data);
                }
            }

            $polygon_options['id'] = $label . $this->getRandomId();
            $row .= '"';
            foreach ($polygon_options as $option => $val) {
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
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS MULTIPOLYGON object
     * @param array  $fill_color Color for the GIS MULTIPOLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, int $srid, $label, $fill_color, array $scale_data)
    {
        $fill_color[] = 0.8;
        $fill_style = ['color' => $fill_color];
        $stroke_style = [
            'color' => [0,0,0],
            'width' => 0.5,
        ];
        $row = 'var style = new ol.style.Style({'
            . 'fill: new ol.style.Fill(' . json_encode($fill_style) . '),'
            . 'stroke: new ol.style.Stroke(' . json_encode($stroke_style) . ')';

        if (trim($label) !== '') {
            $text_style = ['text' => trim($label)];
            $row .= ',text: new ol.style.Text(' . json_encode($text_style) . ')';
        }

        $row .= '});';

        if ($srid === 0) {
            $srid = 4326;
        }

        $row .= $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($spatial, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        return $row . $this->getPolygonArrayForOpenLayers($polygons, $srid)
            . 'var multiPolygon = new ol.geom.MultiPolygon(polygonArray);'
            . 'var feature = new ol.Feature(multiPolygon);'
            . 'feature.setStyle(style);'
            . 'vectorLayer.addFeature(feature);';
    }

    /**
     * Draws a ring of the polygon using SVG path element.
     *
     * @param string $polygon    The ring
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code to draw the ring
     */
    private function drawPath($polygon, array $scale_data)
    {
        $points_arr = $this->extractPoints($polygon, $scale_data);

        $row = ' M ' . $points_arr[0][0] . ', ' . $points_arr[0][1];
        $other_points = array_slice($points_arr, 1, count($points_arr) - 2);
        foreach ($other_points as $point) {
            $row .= ' L ' . $point[0] . ', ' . $point[1];
        }

        $row .= ' Z ';

        return $row;
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
        $data_row = $gis_data[$index]['MULTIPOLYGON'] ?? null;

        $no_of_polygons = $data_row['no_of_polygons'] ?? 1;
        if ($no_of_polygons < 1) {
            $no_of_polygons = 1;
        }

        $wkt = 'MULTIPOLYGON(';
        for ($k = 0; $k < $no_of_polygons; $k++) {
            $no_of_lines = $data_row[$k]['no_of_lines'] ?? 1;
            if ($no_of_lines < 1) {
                $no_of_lines = 1;
            }

            $wkt .= '(';
            for ($i = 0; $i < $no_of_lines; $i++) {
                $no_of_points = $data_row[$k][$i]['no_of_points'] ?? 4;
                if ($no_of_points < 4) {
                    $no_of_points = 4;
                }

                $wkt .= '(';
                for ($j = 0; $j < $no_of_points; $j++) {
                    $wkt .= (isset($data_row[$k][$i][$j]['x'])
                            && trim((string) $data_row[$k][$i][$j]['x']) != ''
                            ? $data_row[$k][$i][$j]['x'] : $empty)
                        . ' ' . (isset($data_row[$k][$i][$j]['y'])
                            && trim((string) $data_row[$k][$i][$j]['y']) != ''
                            ? $data_row[$k][$i][$j]['y'] : $empty) . ',';
                }

                $wkt = mb_substr($wkt, 0, -1);
                $wkt .= '),';
            }

            $wkt = mb_substr($wkt, 0, -1);
            $wkt .= '),';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
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
        // Determines whether each line ring is an inner ring or an outer ring.
        // If it's an inner ring get a point on the surface which can be used to
        // correctly classify inner rings to their respective outer rings.
        foreach ($row_data['parts'] as $i => $ring) {
            $row_data['parts'][$i]['isOuter'] = GisPolygon::isOuterRing($ring['points']);
        }

        // Find points on surface for inner rings
        foreach ($row_data['parts'] as $i => $ring) {
            if ($ring['isOuter']) {
                continue;
            }

            $row_data['parts'][$i]['pointOnSurface'] = GisPolygon::getPointOnSurface($ring['points']);
        }

        // Classify inner rings to their respective outer rings.
        foreach ($row_data['parts'] as $j => $ring1) {
            if ($ring1['isOuter']) {
                continue;
            }

            foreach ($row_data['parts'] as $k => $ring2) {
                if (! $ring2['isOuter']) {
                    continue;
                }

                // If the pointOnSurface of the inner ring
                // is also inside the outer ring
                if (! GisPolygon::isPointInsidePolygon($ring1['pointOnSurface'], $ring2['points'])) {
                    continue;
                }

                if (! isset($ring2['inner'])) {
                    $row_data['parts'][$k]['inner'] = [];
                }

                $row_data['parts'][$k]['inner'][] = $j;
            }
        }

        $wkt = 'MULTIPOLYGON(';
        // for each polygon
        foreach ($row_data['parts'] as $ring) {
            if (! $ring['isOuter']) {
                continue;
            }

            $wkt .= '('; // start of polygon

            $wkt .= '('; // start of outer ring
            foreach ($ring['points'] as $point) {
                $wkt .= $point['x'] . ' ' . $point['y'] . ',';
            }

            $wkt = mb_substr($wkt, 0, -1);
            $wkt .= ')'; // end of outer ring

            // inner rings if any
            if (isset($ring['inner'])) {
                foreach ($ring['inner'] as $j) {
                    $wkt .= ',('; // start of inner ring
                    foreach ($row_data['parts'][$j]['points'] as $innerPoint) {
                        $wkt .= $innerPoint['x'] . ' ' . $innerPoint['y'] . ',';
                    }

                    $wkt = mb_substr($wkt, 0, -1);
                    $wkt .= ')'; // end of inner ring
                }
            }

            $wkt .= '),'; // end of polygon
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value Value of the GIS column
     * @param int    $index Index of the geometry
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
            $params[$index]['gis_type'] = 'MULTIPOLYGON';
            $wkt = $value;
        }

        // Trim to remove leading 'MULTIPOLYGON(((' and trailing ')))'
        $multipolygon = mb_substr($wkt, 15, -3);
        // Separate each polygon
        $polygons = explode(')),((', $multipolygon);

        $param_row =& $params[$index]['MULTIPOLYGON'];
        $param_row['no_of_polygons'] = count($polygons);

        $k = 0;
        foreach ($polygons as $polygon) {
            // If the polygon doesn't have an inner polygon
            if (! str_contains($polygon, '),(')) {
                $param_row[$k]['no_of_lines'] = 1;
                $points_arr = $this->extractPoints($polygon, null);
                $no_of_points = count($points_arr);
                $param_row[$k][0]['no_of_points'] = $no_of_points;
                for ($i = 0; $i < $no_of_points; $i++) {
                    $param_row[$k][0][$i]['x'] = $points_arr[$i][0];
                    $param_row[$k][0][$i]['y'] = $points_arr[$i][1];
                }
            } else {
                // Separate outer and inner polygons
                $parts = explode('),(', $polygon);
                $param_row[$k]['no_of_lines'] = count($parts);
                $j = 0;
                foreach ($parts as $ring) {
                    $points_arr = $this->extractPoints($ring, null);
                    $no_of_points = count($points_arr);
                    $param_row[$k][$j]['no_of_points'] = $no_of_points;
                    for ($i = 0; $i < $no_of_points; $i++) {
                        $param_row[$k][$j][$i]['x'] = $points_arr[$i][0];
                        $param_row[$k][$j][$i]['y'] = $points_arr[$i][1];
                    }

                    $j++;
                }
            }

            $k++;
        }

        return $params;
    }
}
