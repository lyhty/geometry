<?php

namespace Lyhty\Geometry\Contracts;

use Lyhty\Geometry\Types\Geometry;

interface MultiGeometryElement
{
    /**
     * Return the count of geometries within the Geometry.
     *
     * @return int
     */
    public function numGeometries(): int;

    /**
     * Return the N geometry of the Geometry.
     *
     * @param int
     */
    public function geometryN(int $n): ?Geometry;

    /**
     * Get the components of the geometry.
     *
     * @return Geometry[]
     */
    public function getComponents(): array;
}
