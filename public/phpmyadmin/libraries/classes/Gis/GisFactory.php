<?php
/**
 * Contains the factory class that handles the creation of geometric objects
 */

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use function strtoupper;

/**
 * Factory class that handles the creation of geometric objects.
 */
class GisFactory
{
    /**
     * Returns the singleton instance of geometric class of the given type.
     *
     * @param string $type type of the geometric object
     *
     * @return GisGeometry|false the singleton instance of geometric class of the given type
     *
     * @static
     */
    public static function factory($type)
    {
        switch (strtoupper($type)) {
            case 'MULTIPOLYGON':
                return GisMultiPolygon::singleton();

            case 'POLYGON':
                return GisPolygon::singleton();

            case 'MULTIPOINT':
                return GisMultiPoint::singleton();

            case 'POINT':
                return GisPoint::singleton();

            case 'MULTILINESTRING':
                return GisMultiLineString::singleton();

            case 'LINESTRING':
                return GisLineString::singleton();

            case 'GEOMETRYCOLLECTION':
                return GisGeometryCollection::singleton();

            default:
                return false;
        }
    }
}
