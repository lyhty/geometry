<?php

namespace Lyhty\Geometry\Adapters;

use GeoIO\Dimension;
use GeoIO\Extractor;
use GeoIO\Factory;
use GeoIO\WKB\Generator\Generator;
use GeoIO\WKB\Parser\Parser;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\GeometryCollection;
use Lyhty\Geometry\Types\LineString;
use Lyhty\Geometry\Types\MultiLineString;
use Lyhty\Geometry\Types\MultiPoint;
use Lyhty\Geometry\Types\MultiPolygon;
use Lyhty\Geometry\Types\Point;
use Lyhty\Geometry\Types\Polygon;

/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/WKB encoder/decoder.
 */
class Wkb extends GeoAdapter implements Factory, Extractor
{
    /**
     * Read WKB into geometry objects.
     *
     * @param  string  $wkb
     *                       Well-known-binary string
     * @param  bool  $isHexString  [optional]
     * @return Geometry
     */
    public function read($wkb, bool $isHexString = false)
    {
        if ($isHexString) {
            $wkb = pack('H*', $wkb);
        } else {
            $wkb = substr($wkb, 4);
        }

        $parser = new Parser($this);

        /** @var Geometry $parsed */
        return $parser->parse($wkb);
    }

    /**
     * Serialize geometries into WKB string.
     *
     * @param  array  $options  [optional]
     * @return string The WKB string representation of the input geometries
     */
    public function write(Geometry $geometry, bool $writeAsHex = false, array $options = [])
    {
        $options = is_array($options) ? $options : [];

        if ($writeAsHex) {
            return (new Generator($this, array_merge(['hex' => true], $options)))
                ->generate($geometry);
        }

        $prefix = pack('L', 0);

        return $prefix.(new Generator($this, $options))->generate($geometry);
    }

    // Read Parser Factory Methods

    public function createPoint($dimension, array $coordinates, $srid = null)
    {
        return tap(
            new Point($coordinates['x'], $coordinates['y']),
            fn (Point $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createLineString($dimension, array $points, $srid = null)
    {
        return tap(
            new LineString($points),
            fn (LineString $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createLinearRing($dimension, array $points, $srid = null)
    {
        return tap(
            new LineString($points),
            fn (LineString $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createPolygon($dimension, array $lineStrings, $srid = null)
    {
        return tap(
            new Polygon($lineStrings),
            fn (Polygon $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createMultiPoint($dimension, array $points, $srid = null)
    {
        return tap(
            new MultiPoint($points),
            fn (MultiPoint $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createMultiLineString($dimension, array $lineStrings, $srid = null)
    {
        return tap(
            new MultiLineString($lineStrings),
            fn (MultiLineString $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createMultiPolygon($dimension, array $polygons, $srid = null)
    {
        return tap(
            new MultiPolygon($polygons),
            fn (MultiPolygon $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    public function createGeometryCollection($dimension, array $geometries, $srid = null)
    {
        return tap(
            new GeometryCollection($geometries),
            fn (GeometryCollection $geom) => $srid > 0 ? $geom->setSRID($srid) : null
        );
    }

    // Write Generator Extractor Methods

    /**
     * @return bool
     */
    public function supports($geometry)
    {
        return true;
    }

    /**
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @return string One of the Extractor::TYPE_* constants
     */
    public function extractType($geometry)
    {
        return $geometry->geometryType();
    }

    /**
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @return string One of the Dimension::DIMENSION_* constants
     */
    public function extractDimension($geometry)
    {
        return Dimension::DIMENSION_2D;
    }

    /**
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @return int|null
     */
    public function extractSrid($geometry)
    {
        return $geometry->SRID();
    }

    /**
     * Structure of the returned array:.
     *
     * [
     *     'x' => $x, // float
     *     'y' => $y, // float
     *     'z' => $z, // float|null
     *     'm' => $m  // float|null
     * ]
     *
     * @param  \Lyhty\Geometry\Types\Point  $point
     * @return array
     */
    public function extractCoordinatesFromPoint($point)
    {
        return [
            'x' => $point->getX(),
            'y' => $point->getY(),
            'z' => $point->getZ(),
            'm' => null,
        ];
    }

    /**
     * @param  \Lyhty\Geometry\Types\LineString  $lineString
     * @return array|\Traversable
     */
    public function extractPointsFromLineString($lineString)
    {
        return $lineString->getComponents();
    }

    /**
     * @param  \Lyhty\Geometry\Types\Polygon  $polygon
     * @return array|\Traversable
     */
    public function extractLineStringsFromPolygon($polygon)
    {
        return $polygon->getLineStrings();
    }

    /**
     * @param  \Lyhty\Geometry\Types\MultiPoint  $multiPoint
     * @return array|\Traversable
     */
    public function extractPointsFromMultiPoint($multiPoint)
    {
        return $multiPoint->getComponents();
    }

    /**
     * @param  \Lyhty\Geometry\Types\MultiLineString  $multiLineString
     * @return array|\Traversable
     */
    public function extractLineStringsFromMultiLineString($multiLineString)
    {
        return $multiLineString->getLineStrings();
    }

    /**
     * @param  \Lyhty\Geometry\Types\MultiPolygon  $multiPolygon
     * @return array|\Traversable
     */
    public function extractPolygonsFromMultiPolygon($multiPolygon)
    {
        return $multiPolygon->getPolygons();
    }

    /**
     * @param  \Lyhty\Geometry\Types\GeometryCollection  $geometryCollection
     * @return array|\Traversable
     */
    public function extractGeometriesFromGeometryCollection($geometryCollection)
    {
        return $geometryCollection->getGeometries();
    }
}
