<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Geom;

abstract class HomogenousCollection extends Collection
{
    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function boundary()
    {
        if ($this->isEmpty()) {
            return new LineString;
        }

        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                $componentsBoundaries = [];

                foreach ($this->components as $component) {
                    $componentsBoundaries[] = $component->boundary();
                }

                return Geom::reduce($componentsBoundaries);
            }
        );
    }
}
