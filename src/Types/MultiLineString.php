<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\MultiGeometryElement;

/**
 * @method \Lyhty\Geometry\Types\LineString[] getLineStrings()
 * @method \Lyhty\Geometry\Types\LineString[] getComponents()
 */
class MultiLineString extends HomogenousCollection implements MultiGeometryElement
{
    protected static string $geomType = 'MultiLineString';

    protected static string $collectionComponentClass = LineString::class;

    protected static int $minimumComponentCount = 1;

    /**
     * {@inheritDoc}
     */
    public function boundary(): self
    {
        return $this;
    }

    // MultiLineString is closed if all it's components are closed
    public function isClosed()
    {
        foreach ($this->getComponents() as $line) {
            if (! $line->isClosed()) {
                return false;
            }
        }

        return true;
    }
}
