<?php

namespace Lyhty\Geometry\Adapters;

use Lyhty\Geometry\Contracts\MultiGeometryElement;
use Lyhty\Geometry\Contracts\SingleGeometryElement;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\GeometryCollection;
use Lyhty\Geometry\Types\LineString;
use Lyhty\Geometry\Types\MultiLineString;
use Lyhty\Geometry\Types\MultiPoint;
use Lyhty\Geometry\Types\MultiPolygon;
use Lyhty\Geometry\Types\Point;
use Lyhty\Geometry\Types\Polygon;

/**
 * WKT (Well-Known Text) Adapter.
 */
class Wkt extends GeoAdapter
{
    /**
     * Read WKT string into geometry objects.
     *
     * @param  string  $WKT  A WKT string
     * @return Geometry
     */
    public function read($wkt)
    {
        $wkt = trim($wkt);

        // If it contains a ';', then it contains additional SRID data
        if (strpos($wkt, ';')) {
            $parts = explode(';', $wkt);
            $wkt = $parts[1];
            $eparts = explode('=', $parts[0]);
            $srid = $eparts[1];
        } else {
            $srid = null;
        }

        // If geos is installed, then we take a shortcut and let it parse the WKT
        if ($this->factory->geosInstalled()) {
            $reader = $this->factory->makeWKTReader();

            if ($srid) {
                $geom = $this->factory->geosToGeometry($reader->read($wkt));
                $geom->setSRID($srid);

                return $geom;
            }

            return $this->factory->geosToGeometry($reader->read($wkt));
        }
        $wkt = str_replace(', ', ',', $wkt);

        // For each geometry type, check to see if we have a match at the
        // beginning of the string. If we do, then parse using that type
        foreach (array_keys($this->factory->lcGeometryList()) as $geomType) {
            $wktGeom = strtoupper($geomType);

            if (strtoupper(substr($wkt, 0, strlen($wktGeom))) == $wktGeom) {
                $dataString = $this->getDataString($wkt);
                $method = 'parse'.$geomType;

                if ($srid) {
                    $geom = $this->$method($dataString);
                    $geom->setSRID($srid);

                    return $geom;
                }

                return $this->$method($dataString);
            }
        }
    }

    private function parsePoint($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty point
        if ($dataString == 'EMPTY') {
            return new Point;
        }

        $parts = explode(' ', $dataString);

        return new Point(floatval($parts[0]), floatval($parts[1]));
    }

    private function parseLineString($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty line
        if ($dataString == 'EMPTY') {
            return new LineString;
        }

        $parts = explode(',', $dataString);
        $points = [];
        foreach ($parts as $part) {
            $points[] = $this->parsePoint($part);
        }

        return new LineString($points);
    }

    private function parsePolygon($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty polygon
        if ($dataString == 'EMPTY') {
            return new Polygon;
        }

        $parts = explode('),(', $dataString);
        $lines = [];
        foreach ($parts as $part) {
            if (! $this->beginsWith($part, '(')) {
                $part = '('.$part;
            }
            if (! $this->endsWith($part, ')')) {
                $part = $part.')';
            }
            $lines[] = $this->parseLineString($part);
        }

        return new Polygon($lines);
    }

    private function parseMultiPoint($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty MutiPoint
        if ($dataString == 'EMPTY') {
            return new MultiPoint;
        }

        $parts = explode(',', $dataString);
        $points = [];
        foreach ($parts as $part) {
            $points[] = $this->parsePoint($part);
        }

        return new MultiPoint($points);
    }

    private function parseMultiLineString($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty multi-linestring
        if ($dataString == 'EMPTY') {
            return new MultiLineString;
        }

        $parts = explode('),(', $dataString);
        $lines = [];
        foreach ($parts as $part) {
            // Repair the string if the explode broke it
            if (! $this->beginsWith($part, '(')) {
                $part = '('.$part;
            }
            if (! $this->endsWith($part, ')')) {
                $part = $part.')';
            }
            $lines[] = $this->parseLineString($part);
        }

        return new MultiLineString($lines);
    }

    private function parseMultiPolygon($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty multi-polygon
        if ($dataString == 'EMPTY') {
            return new MultiPolygon;
        }

        $parts = explode(')),((', $dataString);
        $polys = [];
        foreach ($parts as $part) {
            // Repair the string if the explode broke it
            if (! $this->beginsWith($part, '((')) {
                $part = '(('.$part;
            }
            if (! $this->endsWith($part, '))')) {
                $part = $part.'))';
            }
            $polys[] = $this->parsePolygon($part);
        }

        return new MultiPolygon($polys);
    }

    private function parseGeometryCollection($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty geom-collection
        if ($dataString == 'EMPTY') {
            return new GeometryCollection;
        }

        $geometries = [];
        $str = preg_replace('/,\s*([A-Za-z])/', '|$1', $dataString);
        $components = explode('|', trim($str));

        foreach ($components as $component) {
            $geometries[] = $this->read($component);
        }

        return new GeometryCollection($geometries);
    }

    protected function getDataString($wkt)
    {
        $firstParen = strpos($wkt, '(');

        if ($firstParen !== false) {
            return substr($wkt, $firstParen);
        }

        if (strstr($wkt, 'EMPTY')) {
            return 'EMPTY';
        }

        return false;
    }

    /**
     * Trim the parenthesis and spaces.
     */
    protected function trimParens($str)
    {
        $str = trim($str);

        // We want to only strip off one set of parenthesis
        return $this->beginsWith($str, '(')
            ? substr($str, 1, -1)
            : $str;
    }

    protected function beginsWith($str, $char)
    {
        return substr($str, 0, strlen($char)) == $char;
    }

    protected function endsWith($str, $char)
    {
        return substr($str, (0 - strlen($char))) == $char;
    }

    /**
     * Serialize geometries into a WKT string.
     *
     * @param  Geometry  $geometry
     * @return string The WKT string representation of the input geometries
     */
    public function write(Geometry $geometry)
    {
        // If geos is installed, then we take a shortcut and let it write the WKT
        if ($this->factory->geosInstalled()) {
            $writer = $this->factory->makeWKTWriter();
            $writer->setTrim(true);

            return $writer->write($geometry->geos());
        }

        if ($geometry->isEmpty()) {
            return strtoupper($geometry->geometryType()).' EMPTY';
        } elseif ($data = $this->extractData($geometry)) {
            return strtoupper($geometry->geometryType()).' ('.$data.')';
        }
    }

    /**
     * Extract geometry to a WKT string.
     *
     * @param  Geometry  $geometry  A Geometry object
     * @return string
     */
    public function extractData(Geometry $geometry)
    {
        if ($geometry instanceof Point) {
            return $geometry->getX().' '.$geometry->getY();
        }

        $parts = [];

        if ($geometry instanceof SingleGeometryElement) {
            foreach ($geometry->getComponents() as $component) {
                $parts[] = $this->extractData($component);
            }

            return implode(', ', $parts);
        }

        if ($geometry instanceof MultiGeometryElement) {
            foreach ($geometry->getComponents() as $component) {
                $parts[] = '('.$this->extractData($component).')';
            }

            return implode(', ', $parts);
        }

        foreach ($geometry->getComponents() as $component) {
            $parts[] = strtoupper($component->geometryType()).' ('.$this->extractData($component).')';
        }

        return implode(', ', $parts);
    }
}
