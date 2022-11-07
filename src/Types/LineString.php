<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\SimpleCollection;

/**
 * @method \Lyhty\Geometry\Types\Point[] getComponents()
 * @method \Lyhty\Geometry\Types\Point[] getPoints()
 */
class LineString extends HomogenousCollection implements SimpleCollection
{
    /**
     * @var Point[]
     */
    protected array $components = [];

    protected static string $geomType = 'LineString';

    protected static string $collectionComponentClass = Point::class;

    protected static int $minimumComponentCount = 2;

    /**
     * {@inheritDoc}
     */
    public function boundary(): self
    {
        return $this;
    }

    /**
     * Get the "start point" of the line string.
     *
     * @return \Lyhty\Geometry\Types\Point|null
     */
    public function startPoint(): ?Point
    {
        return $this->pointN(1);
    }

    /**
     * Get the "end point" of the line string.
     *
     * @return \Lyhty\Geometry\Types\Point|null
     */
    public function endPoint(): ?Point
    {
        $lastN = $this->numPoints();

        return $this->pointN($lastN);
    }

    /**
     * Return boolean value whether the LineString is closed.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->startPoint()->equals($this->endPoint());
    }

    /**
     * Return boolean value whether the LineString is a ring (closed and simple).
     *
     * @return bool
     */
    public function isRing(): bool
    {
        return $this->isClosed() && $this->isSimple();
    }

    /**
     * The number of points in the geometry.
     *
     * @return int
     */
    public function numPoints(): int
    {
        return $this->numGeometries();
    }

    /**
     * Return the N point of the LineString.
     *
     * @param  int  $n
     * @return \Lyhty\Geometry\Types\Point|null
     */
    public function pointN(int $n): ?Point
    {
        return $this->geometryN($n);
    }

    /**
     * {@inheritDoc}
     */
    public function dimension(): int
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function length(): float
    {
        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                $length = 0;

                foreach ($this->getPointsFlatMap() as $delta => $point) {
                    $previousPoint = $this->pointN($delta);
                    if ($previousPoint) {
                        $length += sqrt(pow(($previousPoint->getX() - $point->getX()), 2) + pow(($previousPoint->getY() - $point->getY()), 2));
                    }
                }

                return $length;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function greatCircleLength($radius = 6378137): float
    {
        $length = 0;
        $points = $this->getPointsFlatMap();
        for ($i = 0; $i < $this->numPoints() - 1; $i++) {
            $point = $points[$i];
            $nextPoint = $points[$i + 1];
            if (! is_object($nextPoint)) {
                continue;
            }
            // Great circle method
            $lat1 = deg2rad($point->getY());
            $lat2 = deg2rad($nextPoint->getY());
            $lon1 = deg2rad($point->getX());
            $lon2 = deg2rad($nextPoint->getX());
            $dlon = $lon2 - $lon1;
            $length +=
                $radius *
                atan2(
                    sqrt(
                        pow(cos($lat2) * sin($dlon), 2) +
                            pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dlon), 2)
                    ),
                    sin($lat1) * sin($lat2) +
                        cos($lat1) * cos($lat2) * cos($dlon)
                );
        }
        // Returns length in meters.
        return $length;
    }

    /**
     * {@inheritDoc}
     */
    public function haversineLength(): float
    {
        $degrees = 0;
        $points = $this->getPointsFlatMap();
        for ($i = 0; $i < $this->numPoints() - 1; $i++) {
            $point = $points[$i];
            $nextPoint = $points[$i + 1];
            if (! is_object($nextPoint)) {
                continue;
            }
            $degree = rad2deg(
                acos(
                    sin(deg2rad($point->getY())) * sin(deg2rad($nextPoint->getY())) +
                        cos(deg2rad($point->getY())) * cos(deg2rad($nextPoint->getY())) *
                        cos(deg2rad(abs($point->getX() - $nextPoint->getX())))
                )
            );
            $degrees += $degree;
        }
        // Returns degrees
        return $degrees;
    }

    /**
     * {@inheritDoc}
     */
    public function explode(): ?array
    {
        $parts = [];
        $points = $this->getPointsFlatMap();

        foreach ($points as $i => $point) {
            if (isset($points[$i + 1])) {
                $parts[] = new LineString([$point, $points[$i + 1]]);
            }
        }

        return $parts;
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
     * Utility function to check if any line sigments intersect.
     *
     * @see http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
     *
     * @param  \Lyhty\Geometry\Types\LineString  $segment
     * @return bool
     */
    public function lineSegmentIntersect($segment)
    {
        $p0X = $this->startPoint()->getX();
        $p0Y = $this->startPoint()->getY();
        $p1X = $this->endPoint()->getX();
        $p1Y = $this->endPoint()->getY();
        $p2X = $segment->startPoint()->getX();
        $p2Y = $segment->startPoint()->getY();
        $p3X = $segment->endPoint()->getX();
        $p3Y = $segment->endPoint()->getY();

        $s1X = $p1X - $p0X;
        $s1Y = $p1Y - $p0Y;
        $s2X = $p3X - $p2X;
        $s2Y = $p3Y - $p2Y;

        $fps = (-$s2X * $s1Y) + ($s1X * $s2Y);
        $fpt = (-$s2X * $s1Y) + ($s1X * $s2Y);

        if ($fps == 0 || $fpt == 0) {
            return false;
        }

        $s = (-$s1Y * ($p0X - $p2X) + $s1X * ($p0Y - $p2Y)) / $fps;
        $t = ($s2X * ($p0Y - $p2Y) - $s2Y * ($p0X - $p2X)) / $fpt;

        if ($s > 0 && $s < 1 && $t > 0 && $t < 1) {
            // Collision detected
            return true;
        }

        return false;
    }
}
