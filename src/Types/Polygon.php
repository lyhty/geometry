<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\SimpleCollection;
use Lyhty\Geometry\Exceptions\GeosNotInstalledException;

/**
 * @method \Lyhty\Geometry\Types\LineString[] getLineStrings()
 * @method \Lyhty\Geometry\Types\LineString[] getComponents()
 */
class Polygon extends HomogenousCollection implements SimpleCollection
{
    /**
     * @var LineString[]
     */
    protected array $components = [];

    protected static string $geomType = 'Polygon';

    protected static string $collectionComponentClass = LineString::class;

    protected static int $minimumComponentCount = 1;

    /**
     * {@inheritDoc}
     */
    public function boundary(): LineString
    {
        return $this->exteriorRing();
    }

    /**
     * {@inheritDoc}
     */
    public function area(bool $exteriorOnly = false, bool $signed = false): float
    {
        if ($this->isEmpty()) {
            return 0;
        }

        if ($exteriorOnly === false) {
            try {
                return $this->geos()->area();
            } catch (GeosNotInstalledException $th) {
            }
        }

        $exteriorRing = $this->exteriorRing();
        $pts = $exteriorRing->getComponents();

        $c = count($pts);
        if ((int) $c == '0') {
            return null;
        }
        $a = '0';
        foreach ($pts as $k => $p) {
            $j = ($k + 1) % $c;
            $a = $a + ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
        }

        if ($signed) {
            $area = ($a / 2);
        } else {
            $area = abs(($a / 2));
        }

        if ($exteriorOnly == true) {
            return $area;
        }

        foreach ($this->components as $delta => $component) {
            if ($delta != 0) {
                $innerPoly = new Polygon([$component]);
                $area -= $innerPoly->area();
            }
        }

        return $area;
    }

    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function centroid(): ?Point
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                $exteriorRing = $this->exteriorRing();
                $pts = $exteriorRing->getComponents();

                $c = count($pts);
                if ((int) $c == '0') {
                    return null;
                }
                $cn = ['x' => '0', 'y' => '0'];
                $a = $this->area(true, true);

                // If this is a polygon with no area. Just return the first point.
                if ($a == 0) {
                    return $this->exteriorRing()->pointN(1);
                }

                foreach ($pts as $k => $p) {
                    $j = ($k + 1) % $c;
                    $P = ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
                    $cn['x'] = $cn['x'] + ($p->getX() + $pts[$j]->getX()) * $P;
                    $cn['y'] = $cn['y'] + ($p->getY() + $pts[$j]->getY()) * $P;
                }

                $cn['x'] = $cn['x'] / (6 * $a);
                $cn['y'] = $cn['y'] / (6 * $a);

                return new Point($cn['x'], $cn['y']);
            }
        );
    }

    /**
     * Find the outermost point from the centroid.
     *
     * @returns Point The outermost point
     */
    public function outermostPoint()
    {
        $centroid = $this->centroid();

        $max = ['length' => 0, 'point' => null];

        foreach ($this->getPointsFlatMap() as $point) {
            $lineString = new LineString([$centroid, $point]);

            if ($lineString->length() > $max['length']) {
                $max['length'] = $lineString->length();
                $max['point'] = $point;
            }
        }

        return $max['point'];
    }

    /**
     * Return the exterior ring of the Polygon.
     *
     * @return LineString
     */
    public function exteriorRing(): LineString
    {
        if ($this->isEmpty()) {
            return new LineString;
        }

        return $this->components[0];
    }

    /**
     * Return the amount of interior rings of the Polygon.
     *
     * @return int
     */
    public function numInteriorRings(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return $this->numGeometries() - 1;
    }

    /**
     * Get the N interior ring of the Polygon.
     *
     * @param  int  $n
     * @return LineString|null
     */
    public function interiorRingN($n): ?LineString
    {
        return $this->geometryN($n + 1);
    }

    /**
     * {@inheritDoc}
     */
    public function dimension(): int
    {
        return 2;
    }

    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function isSimple(): bool
    {
        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                $segments = $this->explode();

                foreach ($segments as $i => $segment) {
                    foreach ($segments as $j => $checkSegment) {
                        if ($i != $j) {
                            if ($segment->lineSegmentIntersect($checkSegment)) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            }
        );
    }

    /**
     * For a given point, determine whether it's bounded by the given polygon.
     * Adapted from http://www.assemblysys.com/dataServices/php_pointinpolygon.php.
     *
     * @see http://en.wikipedia.org/wiki/Point%5Fin%5Fpolygon
     *
     * @param  Point  $point
     * @param  bool  $pointOnBoundary  Whether a boundary should be considered "in" or not
     * @param  bool  $pointOnVertex  Whether a vertex should be considered "in" or not
     * @return bool
     */
    public function pointInPolygon($point, $pointOnBoundary = true, $pointOnVertex = true)
    {
        $vertices = $this->getPointsFlatMap();

        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex($point, $vertices)) {
            return $pointOnVertex ? true : false;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $verticesCount = count($vertices);

        for ($i = 1; $i < $verticesCount; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if (
                $vertex1->getY() == $vertex2->getY()
                && $vertex1->getY() == $point->getY()
                && $point->getX() > min($vertex1->getX(), $vertex2->getX())
                && $point->getX() < max($vertex1->getX(), $vertex2->getX())
            ) {
                // Check if point is on an horizontal polygon boundary
                return $pointOnBoundary ? true : false;
            }
            if (
                $point->getY() > min($vertex1->getY(), $vertex2->getY())
                && $point->getY() <= max($vertex1->getY(), $vertex2->getY())
                && $point->getX() <= max($vertex1->getX(), $vertex2->getX())
                && $vertex1->getY() != $vertex2->getY()
            ) {
                $xinters =
                    ($point->getY() - $vertex1->getY()) * ($vertex2->getX() - $vertex1->getX())
                    / ($vertex2->getY() - $vertex1->getY())
                    + $vertex1->getX();
                if ($xinters == $point->getX()) {
                    // Check if point is on the polygon boundary (other than horizontal)
                    return $pointOnBoundary ? true : false;
                }
                if ($vertex1->getX() == $vertex2->getX() || $point->getX() <= $xinters) {
                    $intersections++;
                }
            }
        }

        // If the number of edges we passed through is even, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return true;
        }

        return false;
    }

    public function pointOnVertex($point)
    {
        foreach ($this->getPointsFlatMap() as $vertex) {
            if ($point->equals($vertex)) {
                return true;
            }
        }
    }
}
