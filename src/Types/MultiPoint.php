<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\MultiGeometryElement;

/**
 * @method \Lyhty\Geometry\Types\Point[] getComponents()
 * @method \Lyhty\Geometry\Types\Point[] getPoints()
 */
class MultiPoint extends HomogenousCollection implements MultiGeometryElement
{
    /**
     * @var Point[]
     */
    protected array $components = [];

    protected static string $geomType = 'MultiPoint';

    protected static string $collectionComponentClass = Point::class;

    protected static int $minimumComponentCount = 1;

    /**
     * {@inheritDoc}
     */
    public function numPoints(): int
    {
        return $this->numGeometries();
    }

    /**
     * {@inheritDoc}
     */
    public function isSimple(): bool
    {
        return true;
    }
}
