"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _control = require("ol/control.js");
var _coordinate = require("ol/coordinate.js");
var _extent = require("ol/extent.js");
var _geom = require("ol/geom.js");
var _layer = require("ol/layer.js");
var _proj = require("ol/proj.js");
var _source = require("ol/source.js");
var _style = require("ol/style.js");
var _ol = require("ol");
const ol = {
  control: {
    Attribution: _control.Attribution,
    MousePosition: _control.MousePosition,
    Zoom: _control.Zoom
  },
  coordinate: {
    createStringXY: _coordinate.createStringXY
  },
  extent: {
    boundingExtent: _extent.boundingExtent
  },
  geom: {
    LineString: _geom.LineString,
    LinearRing: _geom.LinearRing,
    MultiLineString: _geom.MultiLineString,
    MultiPoint: _geom.MultiPoint,
    MultiPolygon: _geom.MultiPolygon,
    Point: _geom.Point,
    Polygon: _geom.Polygon
  },
  layer: {
    Tile: _layer.Tile,
    Vector: _layer.Vector
  },
  proj: {
    fromLonLat: _proj.fromLonLat,
    get: _proj.get,
    transformExtent: _proj.transformExtent
  },
  source: {
    OSM: _source.OSM,
    Vector: _source.Vector
  },
  style: {
    Circle: _style.Circle,
    Fill: _style.Fill,
    Stroke: _style.Stroke,
    Style: _style.Style,
    Text: _style.Text
  },
  Feature: _ol.Feature,
  Map: _ol.Map,
  View: _ol.View
};
var _default = exports.default = ol;