<?php
/**
 * Editor for Geometry data types.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Http\ServerRequest;

use function array_merge;
use function in_array;
use function intval;
use function is_array;
use function mb_strtoupper;
use function preg_match;
use function trim;

/**
 * Editor for Geometry data types.
 */
class GisDataEditorController extends AbstractController
{
    private const GIS_TYPES = [
        'POINT',
        'MULTIPOINT',
        'LINESTRING',
        'MULTILINESTRING',
        'POLYGON',
        'MULTIPOLYGON',
        'GEOMETRYCOLLECTION',
    ];

    public function __invoke(ServerRequest $request): void
    {
        global $gis_data, $geom_type, $gis_obj, $srid, $wkt, $wkt_with_zero;
        global $result, $visualizationSettings, $data, $visualization, $open_layers, $geom_count, $dbi;

        /** @var string|null $field */
        $field = $request->getParsedBodyParam('field');
        /** @var array|null $gisDataParam */
        $gisDataParam = $request->getParsedBodyParam('gis_data');
        /** @var string $type */
        $type = $request->getParsedBodyParam('type', 'GEOMETRY');
        /** @var string|null $value */
        $value = $request->getParsedBodyParam('value');
        /** @var string|null $generate */
        $generate = $request->getParsedBodyParam('generate');
        /** @var string|null $inputName */
        $inputName = $request->getParsedBodyParam('input_name');

        if (! isset($field)) {
            return;
        }

        // Get data if any posted
        $gis_data = [];
        if (is_array($gisDataParam)) {
            $gis_data = $gisDataParam;
        }

        $gis_data = $this->validateGisData($gis_data, $type, $value);
        $geom_type = $gis_data['gis_type'];

        // Generate parameters from value passed.
        $gis_obj = GisFactory::factory($geom_type);
        if ($gis_obj === false) {
            return;
        }

        if (isset($value)) {
            $gis_data = array_merge(
                $gis_data,
                $gis_obj->generateParams($value)
            );
        }

        // Generate Well Known Text
        $srid = isset($gis_data['srid']) && $gis_data['srid'] != '' ? (int) $gis_data['srid'] : 0;
        $wkt = $gis_obj->generateWkt($gis_data, 0);
        $wkt_with_zero = $gis_obj->generateWkt($gis_data, 0, '0');
        $result = "'" . $wkt . "'," . $srid;

        // Generate SVG based visualization
        $visualizationSettings = [
            'width' => 450,
            'height' => 300,
            'spatialColumn' => 'wkt',
            'mysqlVersion' => $dbi->getVersion(),
            'isMariaDB' => $dbi->isMariaDB(),
        ];
        $data = [
            [
                'wkt' => $wkt_with_zero,
                'srid' => $srid,
            ],
        ];
        $visualization = GisVisualization::getByData($data, $visualizationSettings)
            ->toImage('svg');

        $open_layers = GisVisualization::getByData($data, $visualizationSettings)
            ->asOl();

        // If the call is to update the WKT and visualization make an AJAX response
        if ($generate) {
            $this->response->addJSON([
                'result' => $result,
                'visualization' => $visualization,
                'openLayers' => $open_layers,
            ]);

            return;
        }

        $geom_count = 1;
        if ($geom_type === 'GEOMETRYCOLLECTION') {
            $geom_count = isset($gis_data[$geom_type]['geom_count'])
                ? intval($gis_data[$geom_type]['geom_count']) : 1;
            if (isset($gis_data[$geom_type]['add_geom'])) {
                $geom_count++;
            }
        }

        $templateOutput = $this->template->render('gis_data_editor_form', [
            'width' => $visualizationSettings['width'],
            'height' => $visualizationSettings['height'],
            'field' => $field,
            'input_name' => $inputName,
            'srid' => $srid,
            'visualization' => $visualization,
            'open_layers' => $open_layers,
            'column_type' => mb_strtoupper($type),
            'gis_types' => self::GIS_TYPES,
            'geom_type' => $geom_type,
            'geom_count' => $geom_count,
            'gis_data' => $gis_data,
            'result' => $result,
        ]);

        $this->response->addJSON(['gis_editor' => $templateOutput]);
    }

    /**
     * Extract type from the initial call and make sure that it's a valid one.
     * Extract from field's values if available, if not use the column type passed.
     *
     * @param mixed[] $gis_data
     *
     * @return mixed[]
     * @psalm-return array{gis_type:value-of<self::GIS_TYPES>}&mixed[]
     */
    private function validateGisData(array $gis_data, string $type, ?string $value): array
    {
        if (! isset($gis_data['gis_type']) || ! in_array($gis_data['gis_type'], self::GIS_TYPES, true)) {
            if ($type !== '') {
                $gis_data['gis_type'] = mb_strtoupper($type);
            }

            if (isset($value) && trim($value) !== '' && preg_match('/^\'?(\w+)\b/', $value, $matches)) {
                $gis_data['gis_type'] = $matches[1];
            }

            if (! isset($gis_data['gis_type']) || (! in_array($gis_data['gis_type'], self::GIS_TYPES, true))) {
                $gis_data['gis_type'] = self::GIS_TYPES[0];
            }
        }

        return $gis_data;
    }
}
