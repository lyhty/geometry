<?php

namespace Lyhty\Geometry\Contracts;

interface MultiCollection
{
    /**
     * Get the components of the geometry.
     *
     * @return \Lyhty\Geometry\Types\Geometry[]
     */
    public function getComponents(): array;
}
