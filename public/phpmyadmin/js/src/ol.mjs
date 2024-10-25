import { Attribution, MousePosition, Zoom } from 'ol/control.js';
import { createStringXY } from 'ol/coordinate.js';
import { boundingExtent } from 'ol/extent.js';
import { LineString, LinearRing, MultiLineString, MultiPoint, MultiPolygon, Point, Polygon } from 'ol/geom.js';
import { Tile, Vector as VectorLayer } from 'ol/layer.js';
import { fromLonLat, get, transformExtent } from 'ol/proj.js';
import { OSM, Vector as VectorSource } from 'ol/source.js';
import { Circle, Fill, Stroke, Style, Text } from 'ol/style.js';
import { Feature, Map, View } from 'ol';

const ol = {
  control: {
    Attribution, MousePosition, Zoom
  },
  coordinate: {
    createStringXY
  },
  extent: {
    boundingExtent
  },
  geom: {
    LineString, LinearRing, MultiLineString, MultiPoint, MultiPolygon, Point, Polygon
  },
  layer: {
    Tile, Vector: VectorLayer
  },
  proj: {
    fromLonLat, get, transformExtent
  },
  source: {
    OSM, Vector: VectorSource
  },
  style: {
    Circle, Fill, Stroke, Style, Text
  },
  Feature, Map, View
}

export default ol;