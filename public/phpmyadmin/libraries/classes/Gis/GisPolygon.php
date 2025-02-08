<?php
/**
 * Handles actions related to GIS POLYGON objects
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
use function max;
use function mb_substr;
use function min;
use function round;
use function sqrt;
use function str_contains;
use function trim;

/**
 * Handles actions related to GIS POLYGON objects
 */
class GisPolygon extends GisGeometry
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
     * @return GisPolygon the singleton
     */
    public static function singleton()
    {
        if (! isset(self::$instance)) {
            self::$instance = new GisPolygon();
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
        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        // If the polygon doesn't have an inner ring, use polygon itself
        if (! str_contains($polygon, '),(')) {
            $ring = $polygon;
        } else {
            // Separate outer ring and use it to determine min-max
            $parts = explode('),(', $polygon);
            $ring = $parts[0];
        }

        return $this->setMinMax($ring, GisGeometry::EMPTY_EXTENT);
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

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

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
        // print label if applicable
        if ($label !== '') {
            $image->string(
                1,
                (int) round($points_arr[2]),
                (int) round($points_arr[3]),
                $label,
                $black
            );
        }

        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string      $spatial    GIS POLYGON object
     * @param string|null $label      Label for the GIS POLYGON object
     * @param string      $fill_color Color for the GIS POLYGON object
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

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

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
        // print label if applicable
        if ($label !== '') {
            $pdf->setXY($points_arr[2], $points_arr[3]);
            $pdf->setFontSize(5);
            $pdf->Cell(0, 0, $label);
        }

        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial    GIS POLYGON object
     * @param string $label      Label for the GIS POLYGON object
     * @param string $fill_color Color for the GIS POLYGON object
     * @param array  $scale_data Array containing data related to scaling
     *
     * @return string the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $fill_color, array $scale_data)
    {
        $polygon_options = [
            'data-label' => $label,
            'id' => $label . $this->getRandomId(),
            'class' => 'polygon vector',
            'stroke' => 'black',
            'stroke-width' => 0.5,
            'fill' => $fill_color,
            'fill-rule' => 'evenodd',
            'fill-opacity' => 0.8,
        ];

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        $row = '<path d="';

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

        $row .= '"';
        foreach ($polygon_options as $option => $val) {
            $row .= ' ' . $option . '="' . trim((string) $val) . '"';
        }

        $row .= '/>';

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial    GIS POLYGON object
     * @param int    $srid       Spatial reference ID
     * @param string $label      Label for the GIS POLYGON object
     * @param array  $fill_color Color for the GIS POLYGON object
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

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($spatial, 9, -2);

        // Separate outer and inner polygons
        $parts = explode('),(', $polygon);

        return $row . $this->getPolygonForOpenLayers($parts, $srid)
            . 'var feature = new ol.Feature({geometry: polygon});'
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
        $no_of_lines = $gis_data[$index]['POLYGON']['no_of_lines'] ?? 1;
        if ($no_of_lines < 1) {
            $no_of_lines = 1;
        }

        $wkt = 'POLYGON(';
        for ($i = 0; $i < $no_of_lines; $i++) {
            $no_of_points = $gis_data[$index]['POLYGON'][$i]['no_of_points'] ?? 4;
            if ($no_of_points < 4) {
                $no_of_points = 4;
            }

            $wkt .= '(';
            for ($j = 0; $j < $no_of_points; $j++) {
                $wkt .= (isset($gis_data[$index]['POLYGON'][$i][$j]['x'])
                        && trim((string) $gis_data[$index]['POLYGON'][$i][$j]['x']) != ''
                        ? $gis_data[$index]['POLYGON'][$i][$j]['x'] : $empty)
                    . ' ' . (isset($gis_data[$index]['POLYGON'][$i][$j]['y'])
                        && trim((string) $gis_data[$index]['POLYGON'][$i][$j]['y']) != ''
                        ? $gis_data[$index]['POLYGON'][$i][$j]['y'] : $empty) . ',';
            }

            $wkt = mb_substr($wkt, 0, -1);
            $wkt .= '),';
        }

        $wkt = mb_substr($wkt, 0, -1);

        return $wkt . ')';
    }

    /**
     * Calculates the area of a closed simple polygon.
     *
     * @param array $ring array of points forming the ring
     *
     * @return float the area of a closed simple polygon
     *
     * @static
     */
    public static function area(array $ring)
    {
        $no_of_points = count($ring);

        // If the last point is same as the first point ignore it
        $last = count($ring) - 1;
        if (($ring[0]['x'] == $ring[$last]['x']) && ($ring[0]['y'] == $ring[$last]['y'])) {
            $no_of_points--;
        }

        //         _n-1
        // A = _1_ \    (X(i) * Y(i+1)) - (Y(i) * X(i+1))
        //      2  /__
        //         i=0
        $area = 0;
        for ($i = 0; $i < $no_of_points; $i++) {
            $j = ($i + 1) % $no_of_points;
            $area += $ring[$i]['x'] * $ring[$j]['y'];
            $area -= $ring[$i]['y'] * $ring[$j]['x'];
        }

        $area /= 2.0;

        return $area;
    }

    /**
     * Determines whether a set of points represents an outer ring.
     * If points are in clockwise orientation then, they form an outer ring.
     *
     * @param array $ring array of points forming the ring
     *
     * @static
     */
    public static function isOuterRing(array $ring): bool
    {
        // If area is negative then it's in clockwise orientation,
        // i.e. it's an outer ring
        return self::area($ring) < 0;
    }

    /**
     * Determines whether a given point is inside a given polygon.
     *
     * @param array $point   x, y coordinates of the point
     * @param array $polygon array of points forming the ring
     *
     * @static
     */
    public static function isPointInsidePolygon(array $point, array $polygon): bool
    {
        // If first point is repeated at the end remove it
        $last = count($polygon) - 1;
        if (($polygon[0]['x'] == $polygon[$last]['x']) && ($polygon[0]['y'] == $polygon[$last]['y'])) {
            $polygon = array_slice($polygon, 0, $last);
        }

        $no_of_points = count($polygon);
        $counter = 0;

        // Use ray casting algorithm
        $p1 = $polygon[0];
        for ($i = 1; $i <= $no_of_points; $i++) {
            $p2 = $polygon[$i % $no_of_points];
            if ($point['y'] <= min([$p1['y'], $p2['y']])) {
                $p1 = $p2;
                continue;
            }

            if ($point['y'] > max([$p1['y'], $p2['y']])) {
                $p1 = $p2;
                continue;
            }

            if ($point['x'] > max([$p1['x'], $p2['x']])) {
                $p1 = $p2;
                continue;
            }

            if ($p1['y'] != $p2['y']) {
                $xinters = ($point['y'] - $p1['y'])
                    * ($p2['x'] - $p1['x'])
                    / ($p2['y'] - $p1['y']) + $p1['x'];
                if ($p1['x'] == $p2['x'] || $point['x'] <= $xinters) {
                    $counter++;
                }
            }

            $p1 = $p2;
        }

        return $counter % 2 != 0;
    }

    /**
     * Returns a point that is guaranteed to be on the surface of the ring.
     * (for simple closed rings)
     *
     * @param array $ring array of points forming the ring
     *
     * @return array|false a point on the surface of the ring
     */
    public static function getPointOnSurface(array $ring)
    {
        $x0 = null;
        $x1 = null;
        $y0 = null;
        $y1 = null;
        // Find two consecutive distinct points.
        for ($i = 0, $nb = count($ring) - 1; $i < $nb; $i++) {
            if ($ring[$i]['y'] != $ring[$i + 1]['y']) {
                $x0 = $ring[$i]['x'];
                $x1 = $ring[$i + 1]['x'];
                $y0 = $ring[$i]['y'];
                $y1 = $ring[$i + 1]['y'];
                break;
            }
        }

        if (! isset($x0)) {
            return false;
        }

        // Find the mid point
        $x2 = ($x0 + $x1) / 2;
        $y2 = ($y0 + $y1) / 2;

        // Always keep $epsilon < 1 to go with the reduction logic down here
        $epsilon = 0.1;
        $denominator = sqrt(($y1 - $y0) ** 2 + ($x0 - $x1) ** 2);
        $pointA = [];
        $pointB = [];

        while (true) {
            // Get the points on either sides of the line
            // with a distance of epsilon to the mid point
            $pointA['x'] = $x2 + ($epsilon * ($y1 - $y0)) / $denominator;
            $pointA['y'] = $y2 + ($pointA['x'] - $x2) * ($x0 - $x1) / ($y1 - $y0);

            $pointB['x'] = $x2 + ($epsilon * ($y1 - $y0)) / (0 - $denominator);
            $pointB['y'] = $y2 + ($pointB['x'] - $x2) * ($x0 - $x1) / ($y1 - $y0);

            // One of the points should be inside the polygon,
            // unless epsilon chosen is too large
            if (self::isPointInsidePolygon($pointA, $ring)) {
                return $pointA;
            }

            if (self::isPointInsidePolygon($pointB, $ring)) {
                return $pointB;
            }

            //If both are outside the polygon reduce the epsilon and
            //recalculate the points(reduce exponentially for faster convergence)
            $epsilon **= 2;
            if ($epsilon == 0) {
                return false;
            }
        }
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
            $params[$index]['gis_type'] = 'POLYGON';
            $wkt = $value;
        }

        // Trim to remove leading 'POLYGON((' and trailing '))'
        $polygon = mb_substr($wkt, 9, -2);
        // Separate each linestring
        $linerings = explode('),(', $polygon);
        $params[$index]['POLYGON']['no_of_lines'] = count($linerings);

        $j = 0;
        foreach ($linerings as $linering) {
            $points_arr = $this->extractPoints($linering, null);
            $no_of_points = count($points_arr);
            $params[$index]['POLYGON'][$j]['no_of_points'] = $no_of_points;
            for ($i = 0; $i < $no_of_points; $i++) {
                $params[$index]['POLYGON'][$j][$i]['x'] = $points_arr[$i][0];
                $params[$index]['POLYGON'][$j][$i]['y'] = $points_arr[$i][1];
            }

            $j++;
        }

        return $params;
    }
}
