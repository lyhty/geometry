<?php

namespace Lyhty\Geometry\Concerns;

use Illuminate\Support\Arr;
use Lyhty\Geometry\Geom;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\LineString;
use Lyhty\Geometry\Types\Point;
use Lyhty\Geometry\Types\Polygon;

trait Operations
{
    /**
     * The area of this Polygon (or GeometryCollection), as measured in the spatial
     * reference system of the geometry.
     */
    public function area(): float
    {
        return 0;
    }

    /**
     * Returns a geometric object that represents all Points whose distance from this
     * geometric object is less than or equal to distance. Calculations are in the
     * spatial reference system of this geometric object. Because of the limitations
     * of linear interpolation, there will often be some relatively small error in
     * this distance, but it should be near the resolution of the coordinates used.
     *
     * @uses geos
     */
    public function buffer(float $distance): Geometry
    {
        return $this->forwardCallToGeos(__FUNCTION__, $distance);
    }

    /**
     * @uses geos
     */
    public function checkValidity(): bool
    {
        return data_get($this->forwardCallToGeos(__FUNCTION__), 'valid', false);
    }

    /**
     * Returns a geometric object that represents the convex hull of this geometric object.
     *
     * @see http://en.wikipedia.org/wiki/Convex_hull
     *
     * @uses geos
     */
    public function convexHull(): Geometry
    {
        return $this->forwardCallToGeos(__FUNCTION__);
    }

    /**
     * Returns a geometric object that represents the Point set difference of this
     * geometric object with the given geometry.
     *
     * @uses geos
     */
    public function difference(Geometry $geometry): Geometry
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Returns the shortest distance between any two Points in the two geometric
     * objects as calculated in the spatial reference system of this geometric
     * object. Because the geometries are closed, it is possible to find a point on
     * each geometric object involved, such that the distance between these 2 points
     * is the returned distance between their geometric objects.
     *
     * @uses geos
     */
    public function distance(Geometry $geometry): float
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * The minimum bounding box for this Geometry, returned as a Geometry.
     *
     * @uses geos
     */
    public function envelope(): Polygon
    {
        if ($this->isEmpty()) {
            return new Polygon;
        }

        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                $bbox = $this->getBBox();

                $points = [
                    new Point($bbox['maxx'], $bbox['miny']),
                    new Point($bbox['maxx'], $bbox['maxy']),
                    new Point($bbox['minx'], $bbox['maxy']),
                    new Point($bbox['minx'], $bbox['miny']),
                    new Point($bbox['maxx'], $bbox['miny']),
                ];

                $outerBoundary = new LineString($points);

                return new Polygon([$outerBoundary]);
            }
        );
    }

    /**
     * @see https://en.wikipedia.org/wiki/Great-circle_distance
     */
    public function greatCircleLength(): float
    {
        return 0;
    }

    /**
     * @see http://en.wikipedia.org/wiki/Hausdorff_distance
     *
     * @uses geos
     */
    public function hausdorffDistance(Geometry $geometry): float
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * @see https://en.wikipedia.org/wiki/Haversine_formula
     */
    public function haversineLength(): float
    {
        return 0;
    }

    /**
     * Returns a geometric object that represents the point set intersection of this
     * geometric object with the given geometry.
     *
     * @see http://en.wikipedia.org/wiki/Intersection_(set_theory)
     *
     * @uses geos
     */
    public function intersection(Geometry $geometry): Geometry
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Get the length of the geometry in its associated spatial reference.
     */
    public function length(): float
    {
        return 0;
    }

    /**
     * Computes the intersection matrix for the spatial relationship with
     * the given geometry.
     *
     * @uses geos
     *
     * @param  mixed  $pattern
     * @return void
     */
    public function relate(Geometry $geometry, $pattern = null)
    {
        return $pattern
            ? $this->forwardCallToGeos(__FUNCTION__, [$geometry, $pattern])
            : $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Simplify the geometry using the standard Douglas-Peucker algorithm.
     *
     * @uses geos
     */
    public function simplify(float $tolerance, bool $preserveTopology = false): Geometry
    {
        return $this->forwardCallToGeos(__FUNCTION__, [$tolerance, $preserveTopology]);
    }

    /**
     * Returns a geometric object that represents the point set symmetric difference
     * of this geometric object with the given geometry.
     *
     * @see http://en.wikipedia.org/wiki/Symmetric_difference
     *
     * @uses geos
     */
    public function symDifference(Geometry $geometry): Geometry
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Returns a geometric object that represents the Point set union of this
     * geometric object with the given geometry.
     *
     * @see http://en.wikipedia.org/wiki/Union_(set_theory)
     *
     * @uses geos
     *
     * @param  \Lyhty\Geometry\Types\Geometry|\Lyhty\Geometry\Types\Geometry[]  $geometry
     * @return void
     */
    public function union(Geometry|array $geometry): Geometry
    {
        $geom = $this->geos();

        foreach (Arr::wrap($geometry) as $item) {
            $geom = $geom->union($item->geos());
        }

        return Geom::geosToGeometry($geom);
    }
}
