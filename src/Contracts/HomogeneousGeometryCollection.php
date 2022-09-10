<?php

namespace Lyhty\Geometry\Contracts;

use Lyhty\Geometry\Types\Geometry;

interface HomogeneousGeometryCollection
{
    public function boundary(): Geometry;
}
