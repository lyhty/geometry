<?php

namespace Lyhty\Geometry\Adapters;

use Exception;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\GeometryCollection;
use Lyhty\Geometry\Types\LineString;
use Lyhty\Geometry\Types\MultiLineString;
use Lyhty\Geometry\Types\MultiPoint;
use Lyhty\Geometry\Types\MultiPolygon;
use Lyhty\Geometry\Types\Point;
use Lyhty\Geometry\Types\Polygon;

/**
 * GeoJSON class : a geojson reader/writer.
 *
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class GeoJson extends GeoAdapter
{
    /**
     * Given an object or a string, return a Geometry.
     *
     * @param  mixed  $input  The GeoJSON string or object
     * @return object Geometry
     */
    public function read($input)
    {
        if (is_string($input)) {
            $input = json_decode($input);
        }
        if (! is_object($input) || ! is_string($input->type)) {
            throw new Exception('Invalid JSON');
        }

        // Check to see if it's a FeatureCollection
        if ($input->type === 'FeatureCollection') {
            $geoms = [];
            foreach ($input->features as $feature) {
                $geoms[] = $this->read($feature);
            }

            return $this->factory->reduce($geoms);
        }

        // Check to see if it's a Feature
        if ($input->type === 'Feature') {
            return $this->read($input->geometry);
        }

        // It's a geometry - process it
        return $this->objToGeom($input);
    }

    private function objToGeom($obj)
    {
        $type = $obj->type;

        return $type === GeometryCollection::geometryType()
            ? $this->objToGeometryCollection($obj)
            : $this->{'arrayTo'.$type}($obj->coordinates);
    }

    private function arrayToPoint($array)
    {
        if (! empty($array)) {
            return new Point($array[0], $array[1]);
        }

        return new Point;
    }

    private function arrayToLineString($array)
    {
        $points = [];
        foreach ($array as $compArray) {
            $points[] = $this->arrayToPoint($compArray);
        }

        return new LineString($points);
    }

    private function arrayToPolygon($array)
    {
        $lines = [];
        foreach ($array as $compArray) {
            $lines[] = $this->arrayToLineString($compArray);
        }

        return new Polygon($lines);
    }

    private function arrayToMultiPoint($array)
    {
        $points = [];
        foreach ($array as $compArray) {
            $points[] = $this->arrayToPoint($compArray);
        }

        return new MultiPoint($points);
    }

    private function arrayToMultiLineString($array)
    {
        $lines = [];
        foreach ($array as $compArray) {
            $lines[] = $this->arrayToLineString($compArray);
        }

        return new MultiLineString($lines);
    }

    private function arrayToMultiPolygon($array)
    {
        $polys = [];
        foreach ($array as $compArray) {
            $polys[] = $this->arrayToPolygon($compArray);
        }

        return new MultiPolygon($polys);
    }

    private function objToGeometryCollection($obj)
    {
        $geoms = [];
        if (empty($obj->geometries)) {
            throw new Exception('Invalid GeoJSON: GeometryCollection with no component geometries');
        }
        foreach ($obj->geometries as $compObject) {
            $geoms[] = $this->objToGeom($compObject);
        }

        return new GeometryCollection($geoms);
    }

    /**
     * Serializes an object into a geojson string.
     *
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @param  bool  $returnArray
     * @return string|array
     */
    public function write(Geometry $geometry, bool $returnArray = false): string|array
    {
        return $returnArray
            ? $geometry->toArray()
            : json_encode($geometry->toArray());
    }
}
