<?php

namespace Lyhty\Geometry\Concerns;

use Lyhty\Geometry\Types\Geometry;

trait Predicates
{
    /**
     * Return boolean value whether the geometry contains with the given geometry.
     *
     * @uses geos
     */
    public function contains(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry covers the given geometry.
     *
     * @uses geos
     */
    public function covers(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry is covered by the given geometry.
     *
     * @uses geos
     */
    public function coveredBy(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry crosses with the given geometry.
     *
     * @uses geos
     */
    public function crosses(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry disjoint with the given geometry.
     *
     * @uses geos
     */
    public function disjoint(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry is “spatially equal” to
     * the given Geometry.
     */
    abstract public function equals(Geometry $geometry): bool;

    /**
     * Return boolean value whether this gemometric object is exactly the same as
     * another object, including the ordering of component parts.
     *
     * @uses geos
     */
    public function equalsExact(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry intersects with the given geometry.
     *
     * @uses geos
     */
    public function intersects(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry overlaps with the given geometry.
     *
     * @uses geos
     */
    public function overlaps(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry touches with the given geometry.
     *
     * @uses geos
     */
    public function touches(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }

    /**
     * Return boolean value whether the geometry is within the given geometry.
     *
     * @uses geos
     */
    public function within(Geometry $geometry): bool
    {
        return $this->forwardCallToGeos(__FUNCTION__, $geometry);
    }
}
